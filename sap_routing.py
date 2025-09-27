# nama file: sap_routing.py

import os
from flask import Flask, request, jsonify
from pyrfc import Connection, ABAPApplicationError, ABAPRuntimeError
from datetime import datetime
import logging
from flask_cors import CORS

# Format logging disesuaikan
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - [%(funcName)s] - %(message)s'
)

app = Flask(__name__)
CORS(app)

# --- FUNGSI HELPER ---
def connect_sap(user, passwd):
    """Membuka koneksi ke SAP menggunakan kredensial yang diberikan."""
    try:
        return Connection(
            user=user,
            passwd=passwd,
            ashost=os.getenv("SAP_ASHOST", "192.168.254.154"),
            sysnr=os.getenv("SAP_SYSNR", "01"),
            client=os.getenv("SAP_CLIENT", "300"),
            lang="EN"
        )
    except Exception as e:
        logging.error(f"Gagal saat membuka koneksi ke SAP: {e}")
        return None

# --- ENDPOINT UNTUK MENGAMBIL DESKRIPSI WORK CENTER ---
@app.route('/get_work_center_desc', methods=['POST'])
def get_work_center_desc():
    data = request.get_json()
    iv_werks = data.get('IV_WERKS')
    iv_arbpl = data.get('IV_ARBPL')

    if not all([iv_werks, iv_arbpl]):
        return jsonify({"error": "Request tidak valid, parameter kurang (membutuhkan IV_WERKS dan IV_ARBPL)."}), 400

    logging.info(f"Menerima permintaan deskripsi untuk Plant: {iv_werks}, Work Center: {iv_arbpl}")
    sap_user = os.getenv("SAP_RFC_USER", "auto_email")
    sap_pass = os.getenv("SAP_RFC_PASS", "11223344")
    conn = None
    try:
        conn = connect_sap(sap_user, sap_pass)
        if not conn:
            return jsonify({"error": f"Koneksi ke SAP dengan user '{sap_user}' gagal."}), 500
        result = conn.call('Z_FM_GET_WC_DESC', IV_WERKS=iv_werks, IV_ARBPL=iv_arbpl)
        logging.info(f"Hasil dari Z_FM_GET_WC_DESC: {result}")
        return_info = result.get('E_RETURN', {})
        description = result.get('E_DESC', '')
        if return_info.get('TYPE') == 'S' and description:
            return jsonify({"description": description})
        else:
            error_message = return_info.get('MESSAGE', 'Deskripsi tidak ditemukan di SAP.')
            return jsonify({"error": error_message}), 404
    except ABAPApplicationError as e:
        logging.error(f"ABAP EXCEPTION untuk {iv_arbpl}: {e.message}")
        return jsonify({"error": f"ABAP Error: {e.message}"})
    except Exception as e:
        logging.error(f"GENERAL EXCEPTION untuk {iv_arbpl}: {str(e)}", exc_info=True)
        return jsonify({"error": f"General Error: {str(e)}"}), 500
    finally:
        if conn:
            conn.close()
            logging.info(f"Koneksi SAP untuk user '{sap_user}' ditutup.")


# --- ENDPOINT UTAMA UNTUK UPLOAD ROUTING (CREATE) ---
@app.route('/upload_routing', methods=['POST'])
def upload_routing():
    data = request.get_json()
    if not all(k in data for k in ['username', 'password', 'routing_data']):
        return jsonify({"error": "Request tidak valid, kekurangan 'username', 'password', atau 'routing_data'"}), 400
    username = data['username']
    password = data['password']
    routing = data['routing_data']
    header = routing.get('header', {})
    operations = routing.get('operations', [])
    material_code_for_log = header.get('IV_MATERIAL', 'N/A')
    logging.info(f"Menerima permintaan untuk memproses Routing Material: {material_code_for_log}")
    conn = None
    try:
        conn = connect_sap(username, password)
        if not conn:
            return jsonify({"status": "Failed", "message": f"Koneksi ke SAP dengan user '{username}' gagal."}), 500
        valid_from_date = header.get('IV_VALID_FROM')
        if not valid_from_date:
            valid_from_date = datetime.now().strftime('%Y%m%d')
        formatted_operations = []
        for op in operations:
            formatted_operations.append({
                'ACTIVITY': op.get('ACTIVITY', ''),'WORK_CNTR': op.get('WORK_CNTR', ''),
                'CONTROL_KEY': op.get('CONTROL_KEY', ''),'DESCRIPTION': op.get('DESCRIPTION', ''),
                'BASE_QUANTITY': str(op.get('BASE_QTY', 1)),'OPERATION_MEASURE_UNIT': str(op.get('UOM', '')),
                'STD_VALUE_01': str(op.get('STD_VALUE_01', 0)), 'STD_UNIT_01': str(op.get('STD_UNIT_01', '')),
                'STD_VALUE_02': str(op.get('STD_VALUE_02', 0)), 'STD_UNIT_02': str(op.get('STD_UNIT_02', '')),
                'STD_VALUE_03': str(op.get('STD_VALUE_03', 0)), 'STD_UNIT_03': str(op.get('STD_UNIT_03', '')),
                'STD_VALUE_04': str(op.get('STD_VALUE_04', 0)), 'STD_UNIT_04': str(op.get('STD_UNIT_04', '')),
                'STD_VALUE_05': str(op.get('STD_VALUE_05', 0)), 'STD_UNIT_05': str(op.get('STD_UNIT_05', '')),
                'STD_VALUE_06': str(op.get('STD_VALUE_06', 0)), 'STD_UNIT_06': str(op.get('STD_UNIT_06', '')),
            })
        material_to_sap = str(header.get('IV_MATERIAL', '')).zfill(18)
        rfc_params = {
            'IV_VALID_FROM': valid_from_date,'IV_TASK_LIST_USAGE': str(header.get('IV_TASK_LIST_USAGE', '')),
            'IV_PLANT': str(header.get('IV_PLANT', '')),'IV_GROUP_COUNTER': str(header.get('IV_GROUP_COUNTER', '1')),
            'IV_TASK_LIST_STATUS': str(header.get('IV_TASK_LIST_STATUS', '')),'IV_LOT_SIZE_TO': str(header.get('IV_LOT_SIZE_TO', '999999999')),
            'IV_DESCRIPTION': str(header.get('IV_DESCRIPTION', '')),'IV_MATERIAL': material_to_sap,
            'IV_SEQUENCE_NO': str(header.get('IV_SEQUENCE_NO', '000000')),'IV_TASK_MEASURE_UNIT': str(header.get('IV_TASK_MEASURE_UNIT', 'PC')),
            'IT_OPERATION': formatted_operations
        }
        logging.info(f"Parameter untuk RFC Z_RFC_ROUTING_CREATE_PROD: {rfc_params}")
        result = conn.call('Z_RFC_ROUTING_CREATE_PROD', **rfc_params)
        logging.info(f"Hasil dari SAP RFC: {result}")
        return_msg = result.get('E_RETURN', {})
        message = return_msg.get('MESSAGE', '').strip()
        msg_type = return_msg.get('TYPE', '')
        task_list_group = None
        message_v2 = return_msg.get('MESSAGE_V2', '')
        if msg_type == 'S' and message_v2:
            parts = message_v2.split('/')
            if len(parts) > 1:
                task_list_group = parts[1]
        if msg_type in ('E', 'A'):
            status = "Failed"
            if not message:
                error_parts = [return_msg.get(f'MESSAGE_V{i}', '') for i in range(1, 5)]
                full_error = ' '.join(filter(None, error_parts))
                if not full_error and return_msg.get('MESSAGE_V1'):
                    full_error = return_msg.get('MESSAGE_V1')
                message = f"Error dari SAP tidak spesifik. Detail: [{full_error.strip()}]" if full_error else "Terjadi error yang tidak diketahui di SAP."
        else:
            status = "Success"
            logging.info(f"RFC Z_RFC_ROUTING_CREATE_PROD berhasil dan melakukan COMMIT di sisi SAP untuk material {material_code_for_log}.")
            if not message:
                message = f"Routing untuk material {material_code_for_log} berhasil dibuat."
        return jsonify({"status": status, "message": message, "task_list_group": task_list_group})
    except (ABAPApplicationError, ABAPRuntimeError) as e:
        logging.error(f"ABAP EXCEPTION untuk {material_code_for_log}: {e.message}")
        return jsonify({"status": "Failed", "message": f"ABAP Error: {e.message}"})
    except Exception as e:
        logging.error(f"GENERAL EXCEPTION untuk {material_code_for_log}: {str(e)}", exc_info=True)
        return jsonify({"status": "Failed", "message": f"General Error: {str(e)}"})
    finally:
        if conn:
            conn.close()
            logging.info(f"Koneksi SAP untuk user '{username}' ditutup.")

# --- [BARU] ENDPOINT BARU UNTUK MENAMBAH OPERASI ROUTING (ADD) ---
@app.route('/add_routing_operation', methods=['POST'])
def add_routing_operation():
    data = request.get_json()
    if not all(k in data for k in ['username', 'password', 'params']):
        return jsonify({"status": "Failed", "message": "Request tidak valid, kekurangan 'username', 'password', atau 'params'"}), 400

    username = data['username']
    password = data['password']
    params = data.get('params', {})

    material_code_for_log = params.get('IV_MATNR', 'N/A')
    operation_for_log = params.get('IV_VORNR', 'N/A')
    logging.info(f"Menerima permintaan Z_RFC_ROUTING_ADD untuk Material: {material_code_for_log}, Operasi: {operation_for_log}")

    conn = None
    try:
        conn = connect_sap(username, password)
        if not conn:
            return jsonify({"status": "Failed", "message": f"Koneksi ke SAP dengan user '{username}' gagal."}), 500

        # Log parameter yang akan dikirim
        logging.info(f"Parameter untuk Z_RFC_ROUTING_ADD: {params}")

        result = conn.call('Z_RFC_ROUTING_ADD', **params)

        logging.info(f"Hasil dari SAP RFC Z_RFC_ROUTING_ADD: {result}")

        # RFC ini mengembalikan tabel pesan ET_MESSAGES
        messages = result.get('ET_MESSAGES', [])
        success = False
        final_message = "Gagal menambahkan operasi routing. Tidak ada pesan sukses dari SAP."

        # Loop melalui pesan untuk menemukan status sukses atau error pertama
        for msg in messages:
            if msg.get('MSGTYP') == 'S':
                success = True
                final_message = msg.get('MSGTX', f"Operasi {operation_for_log} berhasil ditambahkan.")
                break # Keluar dari loop jika sudah menemukan pesan sukses
            elif msg.get('MSGTYP') in ('E', 'A'):
                final_message = msg.get('MSGTX', f"Error saat menambahkan operasi {operation_for_log}.")
                break # Keluar dari loop jika sudah menemukan pesan error

        if success:
            status = "Success"
            logging.info(f"Z_RFC_ROUTING_ADD berhasil untuk material {material_code_for_log}, operasi {operation_for_log}.")
        else:
            status = "Failed"
            logging.error(f"Z_RFC_ROUTING_ADD gagal untuk material {material_code_for_log}, operasi {operation_for_log}: {final_message}")

        return jsonify({"status": status, "message": final_message})

    except (ABAPApplicationError, ABAPRuntimeError) as e:
        error_msg = f"ABAP Error: {e.message}"
        logging.error(f"ABAP EXCEPTION untuk {material_code_for_log}: {error_msg}")
        return jsonify({"status": "Failed", "message": error_msg})
    except Exception as e:
        error_msg = f"General Error: {str(e)}"
        logging.error(f"GENERAL EXCEPTION untuk {material_code_for_log}: {error_msg}", exc_info=True)
        return jsonify({"status": "Failed", "message": error_msg})
    finally:
        if conn:
            conn.close()
            logging.info(f"Koneksi SAP untuk user '{username}' (Z_RFC_ROUTING_ADD) ditutup.")

if __name__ == '__main__':
    app.run(host='127.0.0.1', port=5002, debug=True)
