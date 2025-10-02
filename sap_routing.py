# nama file: sap_routing.py

import os
from flask import Flask, request, jsonify
from pyrfc import Connection, ABAPApplicationError, ABAPRuntimeError
import logging
from flask_cors import CORS
from datetime import datetime
import time # <-- [TAMBAHKAN] Import modul 'time'

# Konfigurasi logging untuk diagnosis yang lebih baik
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
        ashost = os.getenv("SAP_ASHOST", "192.168.254.154")
        sysnr = os.getenv("SAP_SYSNR", "01")
        client = os.getenv("SAP_CLIENT", "300")

        logging.info(f"Mencoba koneksi ke SAP: host={ashost}, sysnr={sysnr}, client={client}, user={user}")

        conn = Connection(
            user=user, passwd=passwd, ashost=ashost,
            sysnr=sysnr, client=client, lang="EN"
        )
        logging.info("Koneksi SAP berhasil dibuat.")
        return conn
    except Exception as e:
        logging.error(f"Gagal saat membuka koneksi ke SAP: {e}")
        return None
def format_material_number(material_code):
    """
    Menambahkan leading zero pada nomor material jika murni numerik.
    Panjang standar SAP untuk nomor material adalah 18 karakter.
    """
    if not material_code:
        return ""

    material_str = str(material_code).strip()

    # Cek apakah string hanya berisi angka (setelah di-strip)
    if material_str.isdigit():
        # Tambahkan leading zero hingga total 18 karakter
        return material_str.zfill(18)
    else:
        # Jika mengandung huruf atau karakter lain, kembalikan apa adanya
        return material_str

# --- [BARU] ENDPOINT UTAMA UNTUK MEMBUAT ROUTING ---
@app.route('/create-routing', methods=['POST'])
def create_routing():
    data = request.get_json()
    username = data.get('username')
    password = data.get('password')
    routing_data = data.get('routing_data')

    if not all([username, password, routing_data]):
        return jsonify({"status": "failed", "message": "Permintaan tidak lengkap."}), 400

    header = routing_data.get('header', {})
    operations = routing_data.get('operations', [])
    material_for_log = header.get('IV_MATERIAL', 'TIDAK DIKETAHUI')

    if not operations:
        return jsonify({"status": "failed", "message": "Tidak ada data operasi."}), 400

    conn = None
    try:
        conn = connect_sap(username, password)
        if not conn:
            return jsonify({"status": "failed", "message": "Koneksi SAP gagal."}), 500

        # --- [PERBAIKAN 1] Tambahkan 'IV_GROUP' ke parameter header ---
        rfc_to_call = 'Z_RFC_ROUTING_CREATE_PROD' # Pastikan nama RFC ini benar
        create_params = {
            'IV_VALID_FROM': datetime.now().strftime('%Y%m%d'),
            'IV_TASK_LIST_USAGE': str(header.get('IV_TASK_LIST_USAGE') or '1'),
            'IV_PLANT': str(header.get('IV_PLANT')),
            'IV_GROUP_COUNTER': str(header.get('IV_GROUP_COUNTER') or '1'),
            'IV_TASK_LIST_STATUS': str(header.get('IV_TASK_LIST_STATUS') or '4'),
            'IV_TASK_MEASURE_UNIT': str(header.get('IV_TASK_MEASURE_UNIT')),
            'IV_LOT_SIZE_TO': '999999999',
            'IV_DESCRIPTION': str(header.get('IV_DESCRIPTION')),
            'IV_MATERIAL': format_material_number(header.get('IV_MATERIAL')),
            'IV_SEQUENCE_NO': '0'
        }
        logging.info(f"Memanggil {rfc_to_call} untuk material {material_for_log} dengan parameter: {create_params}")
        create_result = conn.call(rfc_to_call, **create_params)

        create_return = create_result.get('ET_RETURN', [])
        create_success = any(msg.get('MSGTYP') == 'S' for msg in create_return)

        if not create_success:
            logging.info(f"Header routing untuk material {material_for_log} berhasil dibuat.")

        # --- [PERBAIKAN] Lakukan COMMIT dengan memanggil BAPI_TRANSACTION_COMMIT ---
        try:
            logging.info("Melakukan COMMIT WORK dengan memanggil BAPI_TRANSACTION_COMMIT...")
            # Parameter WAIT='X' akan membuat program menunggu sampai proses update selesai
            commit_result = conn.call('BAPI_TRANSACTION_COMMIT', WAIT='X')
            logging.info(f"BAPI_TRANSACTION_COMMIT berhasil. Hasil: {commit_result}")
        except (ABAPApplicationError, ABAPRuntimeError) as e:
            logging.error(f"Gagal saat memanggil BAPI_TRANSACTION_COMMIT: {e}")
            return jsonify({"status": "failed", "message": f"Gagal saat commit: {e.message}"})
        # -------------------------------------------------------------------------

        # --- Tambahkan jeda 1 detik di sini ---
        logging.info("Memberikan jeda 1 detik sebelum menambahkan operasi...")
        time.sleep(1)
        # ---------------------------------------------------

        # --- LANGKAH 2: PANGGIL RFC UNTUK ADD OPERATIONS (LOOP) ---
        final_return_messages = []
        overall_success = True

        for op in operations:
            add_params = {
                'IV_MATNR':  format_material_number(op.get('IV_MATNR')),
                'IV_WERKS':  str(op.get('IV_WERKS', '')),
                'IV_PLNAL':  str(op.get('IV_PLNAL', '')),
                'IV_VORNR':  str(op.get('IV_VORNR', '')),
                'IV_ARBPL':  str(op.get('IV_ARBPL', '')),
                'IV_STEUS':  str(op.get('IV_STEUS', '')),
                'IV_LTXA1':  str(op.get('IV_LTXA1', '')),
                'IV_BMSCHX': str(op.get('IV_BMSCHX', '1')),
                'IV_VGW01X': str(op.get('IV_VGW01X', '')),
                'IV_VGE01X': str(op.get('IV_VGE01X', '')),
                'IV_VGW02X': str(op.get('IV_VGW02X', '')),
                'IV_VGE02X': str(op.get('IV_VGE02X', '')),
                'IV_VGW03X': str(op.get('IV_VGW03X', '')),
                'IV_VGE03X': str(op.get('IV_VGE03X', '')),
                'IV_VGW04X': str(op.get('IV_VGW04X', '')),
                'IV_VGE04X': str(op.get('IV_VGE04X', '')),
                'IV_VGW05X': str(op.get('IV_VGW05X', '')),
                'IV_VGE05X': str(op.get('IV_VGE05X', '')),
                'IV_VGW06X': str(op.get('IV_VGW06X', '')),
                'IV_VGE06X': str(op.get('IV_VGE06X', '')),
            }
            logging.info(f"Memanggil Z_RFC_ROUTING_ADD untuk operasi {op.get('IV_VORNR')}...")
            add_result = conn.call('Z_RFC_ROUTING_ADD', **add_params)

            add_messages = add_result.get('ET_MESSAGES', [])
            final_return_messages.extend(add_messages)

            # 2. Cek keberhasilan berdasarkan EXPORTING parameter 'LV_SUCCESS'
            #    Di ABAP, abap_true biasanya bernilai 'X'
            operation_success = add_result.get('LV_SUCCESS') == 'X'
            # -----------------------------------------------------------
            if not operation_success:
                overall_success = False

        # --- LANGKAH 3: TENTUKAN HASIL AKHIR ---
        if overall_success:
            status = "success"
            final_message = "Routing berhasil dibuat dan semua operasi ditambahkan."
            logging.info(final_message)
        else:
            status = "failed"
            error_message = "Beberapa operasi gagal ditambahkan."
            for msg in final_return_messages:
                if msg.get('MSGTYP') in ('E', 'A') and msg.get('MSGTX'):
                    error_message = msg.get('MSGTX')
                    break
            final_message = error_message
            logging.error(f"Gagal menambahkan operasi untuk material {material_for_log}: {final_message}")

        return jsonify({"status": status, "message": final_message, "details": final_return_messages})






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

# Endpoint ini tetap dipertahankan untuk fungsionalitas lain jika ada
@app.route('/get_work_center_desc', methods=['POST'])
def get_work_center_desc():
    # ... (kode tidak berubah)
    pass

if __name__ == '__main__':
    app.run(host='127.0.0.1', port=5002, debug=True)

