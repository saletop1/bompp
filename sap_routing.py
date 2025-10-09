# nama file: sap_routing.py

import os
from flask import Flask, request, jsonify
from pyrfc import Connection, ABAPApplicationError, ABAPRuntimeError
import logging
from flask_cors import CORS
from datetime import datetime
import time
import json

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S'
)

app = Flask(__name__)
CORS(app)

def connect_sap(user, passwd):
    """Membuka koneksi ke SAP."""
    try:
        ashost = os.getenv("SAP_ASHOST", "192.168.254.154")
        sysnr = os.getenv("SAP_SYSNR", "01")
        client = os.getenv("SAP_CLIENT", "300")
        conn = Connection(user=user, passwd=passwd, ashost=ashost, sysnr=sysnr, client=client, lang="EN")
        logging.info("Koneksi SAP berhasil dibuat.")
        return conn
    except Exception as e:
        logging.error(f"Gagal saat membuka koneksi ke SAP: {e}")
        return None

@app.route('/create-routing', methods=['POST'])
def create_routing():
    try:
        data = request.get_json()
    except Exception as e:
        logging.error(f"Gagal membaca JSON dari request: {e}")
        return jsonify({"status": "failed", "message": "Request body bukan JSON yang valid."}), 400

    username = data.get('username')
    password = data.get('password')
    routing_data = data.get('routing_data')

    if not all([username, password, routing_data]):
        return jsonify({"status": "failed", "message": "Permintaan tidak lengkap (username/password/routing_data)."}), 400

    header = routing_data.get('header', {})
    operations = routing_data.get('operations', [])
    material_for_log = header.get('IV_MATERIAL', 'N/A')

    logging.info(f"Menerima permintaan pembuatan routing untuk material: '{material_for_log}'")
    # Uncomment baris berikut jika butuh melihat detail payload lengkap untuk debugging
    # logging.info(f"Payload Lengkap: {json.dumps(data, indent=2)}")

    if not operations:
        return jsonify({"status": "failed", "message": "Tidak ada data operasi."}), 400

    conn = None
    try:
        conn = connect_sap(username, password)
        if not conn:
            return jsonify({"status": "failed", "message": "Koneksi SAP gagal."}), 500

        # --- LANGKAH 1: BUAT HEADER ROUTING ---
        create_params = {
            'IV_VALID_FROM': datetime.now().strftime('%Y%m%d'),
            'IV_TASK_LIST_USAGE': str(header.get('IV_TASK_LIST_USAGE') or '1'),
            'IV_PLANT': str(header.get('IV_PLANT')),
            'IV_GROUP_COUNTER': str(header.get('IV_GROUP_COUNTER') or '1'),
            'IV_TASK_LIST_STATUS': str(header.get('IV_TASK_LIST_STATUS') or '4'),
            'IV_TASK_MEASURE_UNIT': str(header.get('IV_TASK_MEASURE_UNIT')),
            'IV_LOT_SIZE_TO': '999999999',
            'IV_DESCRIPTION': str(header.get('IV_DESCRIPTION')),
            'IV_MATERIAL': str(header.get('IV_MATERIAL')),
            'IV_SEQUENCE_NO': '0'
        }
        logging.info(f"Membuat header routing untuk material '{material_for_log}'...")
        create_result = conn.call('Z_RFC_ROUTING_CREATE_PROD', **create_params)

        create_return = create_result.get('ET_RETURN', [])
        header_success = not any(msg.get('MSGTYP') in ('E', 'A') for msg in create_return)

        if not header_success:
            return jsonify({"status": "failed", "message": "Gagal saat membuat header routing."}), 500

        logging.info("Header routing berhasil dibuat, melakukan COMMIT...")
        conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')
        time.sleep(1) # Jeda untuk konsistensi database

        # --- LANGKAH 2: TAMBAHKAN SEMUA OPERASI ---
        final_return_messages = []
        overall_success = True

        for op in operations:
            op_code = op.get('IV_VORNR', 'N/A')
            add_params = {
                'IV_MATNR':  str(op.get('IV_MATNR')),
                'IV_WERKS':  str(op.get('IV_WERKS', '')),
                'IV_PLNAL':  str(op.get('IV_PLNAL', '')).zfill(2),
                'IV_VORNR':  op_code,
                'IV_ARBPL':  str(op.get('IV_ARBPL', '')),
                'IV_STEUS':  str(op.get('IV_STEUS', '')),
                'IV_LTXA1':  str(op.get('IV_LTXA1', '')),
                'IV_BMSCHX': str(op.get('IV_BMSCHX', '')),
            }
            for i in range(1, 7):
                vgw_key = f'IV_VGW0{i}X'
                vge_key = f'IV_VGE0{i}X'
                vgw_val = op.get(vgw_key)
                vge_val = op.get(vge_key) # <-- Ambil juga nilai UOM
                if vgw_val:
                    add_params[vgw_key] = str(vgw_val)
                    if vge_val:
                        add_params[vge_key] = str(vge_val)

            logging.info(f"Menambahkan operasi '{op_code}' untuk material '{material_for_log}'...")
            add_result = conn.call('Z_RFC_ROUTING_ADD', **add_params)

            add_messages = add_result.get('ET_MESSAGES', [])
            final_return_messages.extend(add_messages)

            operation_success = any(
                msg.get('MSGTYP') == 'S' or msg.get('MSGNR') == '344'
                for msg in add_messages
            )

            if not operation_success:
                overall_success = False
                break

        if overall_success:
            status = "success"
            message = "Proses routing selesai dan berhasil."
        else:
            status = "failed"
            message = "Header berhasil dibuat, tetapi gagal pada salah satu operasi."

        return jsonify({"status": status, "message": message, "details": final_return_messages})

    except (ABAPApplicationError, ABAPRuntimeError) as e:
        error_msg = f"ABAP Error: {e.message}"
        logging.error(f"ABAP EXCEPTION: {error_msg}")
        return jsonify({"status": "failed", "message": error_msg}), 500
    except Exception as e:
        error_msg = f"General Error: {str(e)}"
        logging.error(f"GENERAL EXCEPTION: {error_msg}", exc_info=True)
        return jsonify({"status": "failed", "message": error_msg}), 500
    finally:
        if conn:
            conn.close()
            logging.info(f"Koneksi SAP untuk user '{username}' ditutup.")

if __name__ == '__main__':
    app.run(host='127.0.0.1', port=5002, debug=True)
