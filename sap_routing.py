import os
from flask import Flask, request, jsonify
from pyrfc import Connection, ABAPApplicationError
import logging

# Format logging disesuaikan
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - [%(funcName)s] - %(message)s'
)

app = Flask(__name__)

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
    """Endpoint untuk mengambil deskripsi Work Center dari SAP via RFC."""
    data = request.get_json()
    iv_werks = data.get('IV_WERKS')
    iv_arbpl = data.get('IV_ARBPL')

    if not all([iv_werks, iv_arbpl]):
        return jsonify({"error": "Request tidak valid, parameter kurang (membutuhkan IV_WERKS dan IV_ARBPL)."}), 400

    logging.info(f"Menerima permintaan deskripsi untuk Plant: {iv_werks}, Work Center: {iv_arbpl}")

    # [FIX] Gunakan kredensial statis/dari environment variable untuk fungsi internal ini
    # Ganti dengan user SAP yang memiliki otorisasi untuk RFC Z_FM_GET_WC_DESC
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
        return jsonify({"error": f"ABAP Error: {e.message}"}), 500
    except Exception as e:
        logging.error(f"GENERAL EXCEPTION untuk {iv_arbpl}: {str(e)}", exc_info=True)
        return jsonify({"error": f"General Error: {str(e)}"}), 500
    finally:
        if conn:
            conn.close()
            logging.info(f"Koneksi SAP untuk user '{sap_user}' ditutup.")

# --- ENDPOINT UTAMA UNTUK UPLOAD ROUTING ---
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

        formatted_operations = []
        for op in operations:
            formatted_operations.append({
                'ACTIVITY': op.get('ACTIVITY', ''), 'WORK_CNTR': op.get('WORK_CENTER', ''),
                'CONTROL_KEY': op.get('CONTROL_KEY', ''), 'DESCRIPTION': op.get('ls_operation-description', ''),
                'BASE_QUANTITY': str(op.get('BASE_QTY', '1')), 'OPERATION_MEASURE_UNIT': op.get('UOM', ''),
                'STD_VALUE_01': str(op.get('STD_VALUE_01', '0')), 'STD_UNIT_01': op.get('STD_UNIT_01', ''),
                'STD_VALUE_02': str(op.get('STD_VALUE_02', '0')), 'STD_UNIT_02': op.get('STD_UNIT_02', ''),
                'STD_VALUE_03': str(op.get('STD_VALUE_03', '0')), 'STD_UNIT_03': op.get('STD_UNIT_03', ''),
                'STD_VALUE_04': str(op.get('STD_VALUE_04', '0')), 'STD_UNIT_04': op.get('STD_UNIT_04', ''),
                'STD_VALUE_05': str(op.get('STD_VALUE_05', '0')), 'STD_UNIT_05': op.get('STD_UNIT_05', ''),
                'STD_VALUE_06': str(op.get('STD_VALUE_06', '0')), 'STD_UNIT_06': op.get('STD_UNIT_06', ''),
            })

        rfc_params = {
            'iv_valid_from': header.get('IV_VALID_FROM', ''), 'iv_task_list_usage': header.get('IV_TASK_LIST_USAGE', ''),
            'iv_plant': header.get('IV_PLANT', ''), 'iv_group_counter': header.get('IV_GROUP_COUNTER', '1'),
            'iv_task_list_status': header.get('IV_TASK_LIST_STATUS', ''), 'iv_lot_size_to': header.get('IV_LOT_SIZE_TO', '999999999'),
            'iv_description': header.get('IV_DESCRIPTION', ''), 'iv_material': header.get('IV_MATERIAL', ''),
            'it_operation': formatted_operations
        }

        logging.info(f"Parameter untuk RFC Z_RFC_ROUTING_CREATE_PROD: {rfc_params}")
        result = conn.call('Z_RFC_ROUTING_CREATE_PROD', **rfc_params)
        logging.info(f"Hasil dari SAP RFC: {result}")

        return_msg = result.get('E_RETURN', {})
        message = return_msg.get('MESSAGE', '').strip()
        msg_type = return_msg.get('TYPE', '')

        if msg_type == 'E' or msg_type == 'A':
            status = "Failed"
            if not message:
                error_parts = [return_msg.get(f'MESSAGE_V{i}', '') for i in range(1, 5)]
                full_error = ' '.join(filter(None, error_parts))
                message = f"Error dari SAP tidak spesifik. Detail: [{full_error.strip()}]" if full_error else "Terjadi error yang tidak diketahui di SAP."
        else:
            status = "Success"
            if not message:
                message = f"Routing untuk material {material_code_for_log} berhasil dibuat."

        return jsonify({"status": status, "message": message})

    except ABAPApplicationError as e:
        logging.error(f"ABAP EXCEPTION untuk {material_code_for_log}: {e.message}")
        return jsonify({"status": "Failed", "message": f"ABAP Error: {e.message}"})
    except Exception as e:
        logging.error(f"GENERAL EXCEPTION untuk {material_code_for_log}: {str(e)}", exc_info=True)
        return jsonify({"status": "Failed", "message": f"General Error: {str(e)}"})
    finally:
        if conn:
            conn.close()
            logging.info(f"Koneksi SAP untuk user '{username}' ditutup.")

if __name__ == '__main__':
    app.run(host='127.0.0.1', port=5002, debug=True)

