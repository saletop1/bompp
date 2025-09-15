import os
from flask import Flask, request, jsonify
from pyrfc import Connection, ABAPApplicationError
import re
import time
import logging

# Konfigurasi logging dasar untuk output yang lebih bersih
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')

app = Flask(__name__)

# --- FUNGSI HELPER ---
def connect_sap_with_credentials(user, passwd):
    """Membuka koneksi ke SAP menggunakan kredensial yang diberikan."""
    try:
        # Mengambil detail koneksi dari environment variables untuk fleksibilitas
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

def increment_material_code(code):
    """Menghitung kode material berikutnya."""
    match = re.search(r'(.*\D)?(\d+)$', code)
    if not match: return f"{code}-1"
    prefix, number_str = match.groups()
    prefix = prefix or ''
    number = int(number_str)
    padding = len(number_str)
    return f"{prefix}{str(number + 1).zfill(padding)}"

def format_material_code_for_sap(code):
    """Memformat kode material untuk SAP: menghapus semua angka nol di depan."""
    code_str = str(code)
    # Hapus semua angka nol di depan (leading zeros)
    return code_str.lstrip('0')

# === ENDPOINT UPLOAD BOM (DIPERBAIKI TOTAL) ===
@app.route('/upload_bom', methods=['POST'])
def upload_bom():
    data = request.get_json()

    # DEBUG 1: Tampilkan data mentah yang diterima dari Laravel
    logging.info("--- 1. Received raw data from Laravel ---")
    print(data)
    logging.info("---------------------------------------")

    if not all(k in data for k in ['username', 'password', 'boms']):
        return jsonify({"error": "Request tidak valid, kekurangan 'username', 'password', atau 'boms'"}), 400

    username = data['username']
    password = data['password']
    boms_data = data['boms']
    results = []

    for i, bom in enumerate(boms_data):
        conn = None
        # Menggunakan kunci (key) yang BENAR sesuai data dari Laravel
        parent_material = bom.get('IV_MATNR')

        logging.info(f"\n--- 2. Processing BOM #{i+1} for Parent: {parent_material} ---")

        try:
            conn = connect_sap_with_credentials(username, password)
            if not conn:
                results.append({"material_code": parent_material, "status": "Failed", "message": "Connection to SAP failed."})
                continue

            def format_quantity_for_sap(qty_val):
                """Mengonversi angka ke format string yang diminta SAP."""
                if isinstance(qty_val, float) and qty_val == int(qty_val):
                    qty_val = int(qty_val) # Ubah 2.0 menjadi 2

                # Ubah ke string dan ganti titik dengan koma untuk desimal
                return str(qty_val).replace('.', ',')

            # Format kuantitas untuk header
            base_quantity_str = format_quantity_for_sap(bom.get('IV_BMENG', 1))

            # Format kuantitas dan kode material untuk setiap komponen
            formatted_components = []
            for comp in bom.get('IT_COMPONENTS', []):
                comp_qty_str = format_quantity_for_sap(comp.get('COMP_QTY', 0))

                formatted_components.append({
                    'ITEM_CATEG': str(comp.get('ITEM_CATEG', 'L')),
                    'POSNR': str(comp.get('POSNR', '')),
                    'COMPONENT': format_material_code_for_sap(comp.get('COMPONENT', '')),
                    'COMP_QTY': comp_qty_str,
                    'COMP_UNIT': str(comp.get('COMP_UNIT', '')),
                    'PROD_STOR_LOC': str(comp.get('PROD_STOR_LOC', '')),
                    'SCRAP': str(comp.get('SCRAP', '0')),
                    'ITEM_TEXT': str(comp.get('ITEM_TEXT', '')),
                    'ITEM_TEXT2': str(comp.get('ITEM_TEXT2', '')),
                })

            rfc_params = {
                'IV_MATNR': format_material_code_for_sap(bom.get('IV_MATNR', '')),
                'IV_WERKS': str(bom.get('IV_WERKS', '')),
                'IV_STLAN': str(bom.get('IV_STLAN', '')),
                'IV_STLAL': str(bom.get('IV_STLAL', '')),
                'IV_DATUV': str(bom.get('IV_DATUV', '')),
                'IV_BMENG': base_quantity_str, # Menggunakan string yang sudah diformat
                'IV_BMEIN': str(bom.get('IV_BMEIN', '')),
                'IV_STKTX': str(bom.get('IV_STKTX', '')),
                'IT_COMPONENTS': formatted_components # Menggunakan komponen yang sudah diformat
            }

            # DEBUG 3: Tampilkan parameter final yang akan dikirim ke SAP
            logging.info("--- 3. Parameters being sent to SAP RFC ---")
            print(rfc_params)
            logging.info("-----------------------------------------")

            result = conn.call('Z_RFC_UPLOAD_BOM_CS01', **rfc_params)

            # DEBUG 4: Tampilkan hasil dari SAP
            logging.info("--- 4. Result from SAP RFC ---")
            print(result)
            logging.info("------------------------------")

            message = result.get('EX_RETURN_MSG', 'No message returned.')
            status = "Success" if "berhasil" in message.lower() else "Failed"
            results.append({"material_code": parent_material, "status": status, "message": message})

        except ABAPApplicationError as e:
            error_message = e.message
            results.append({"material_code": parent_material, "status": "Failed", "message": error_message})
            logging.error(f"!!! ABAP EXCEPTION for {parent_material}: {error_message}")
        except Exception as e:
            error_message = str(e)
            results.append({"material_code": parent_material, "status": "Failed", "message": error_message})
            logging.error(f"!!! GENERAL EXCEPTION for {parent_material}: {error_message}", exc_info=True)
        finally:
            if conn:
                conn.close()

    return jsonify({ "status": "success", "message": "BOM upload process finished.", "results": results })


# === ENDPOINT LAINNYA (TIDAK DIUBAH) ===

@app.route('/find_material', methods=['GET'])
def find_material_by_description():
    description = request.args.get('description')
    if not description:
        return jsonify({"error": "Parameter 'description' dibutuhkan"}), 400
    user = os.getenv("SAP_USERNAME", "auto_email")
    passwd = os.getenv("SAP_PASSWORD", "11223344")
    conn = None
    try:
        conn = connect_sap_with_credentials(user, passwd)
        if not conn:
            return jsonify({"error": "Tidak bisa terhubung ke SAP dengan kredensial default"}), 500
        result = conn.call('Z_RFC_GET_MATERIAL_BY_DESC', IV_MAKTX=description)
        materials_table = result.get('ET_MATERIAL', [])
        if materials_table:
            materials_table.sort(key=lambda x: x['MATNR'])
            first_match = materials_table[0]
            material_code = first_match.get('MATNR', '').strip()
            if material_code:
                return jsonify({"status": "success", "material_code": material_code})
        return jsonify({"status": "not_found", "message": f"Material dengan deskripsi '{description}' tidak ditemukan di SAP."}), 404
    except Exception as e:
        logging.error(f"Error in /find_material: {str(e)}", exc_info=True)
        return jsonify({"error": str(e)}), 500
    finally:
        if conn:
            conn.close()

@app.route('/get_next_material', methods=['GET'])
def get_next_material():
    material_type = request.args.get('material_type')
    if not material_type: return jsonify({"error": "Parameter 'material_type' dibutuhkan"}), 400
    try:
        conn = connect_sap_with_credentials(user=os.getenv("SAP_USERNAME", "auto_email"), passwd=os.getenv("SAP_PASSWORD", "11223344"))
        if not conn: return jsonify({"error": "Tidak bisa terhubung ke SAP dengan kredensial default"}), 500
        result = conn.call('ZRFC_GET_LAST_MATERIAL_BY_TYPE', I_MTART=material_type)
        last_code = result.get('E_MATNR', '').strip()
        conn.close()
        if not last_code: return jsonify({"error": f"Tidak ada material ditemukan untuk tipe {material_type}"}), 404
        return jsonify({"next_material_code": increment_material_code(last_code)})
    except Exception as e:
        return jsonify({"error": str(e)}), 500

@app.route('/upload_material', methods=['POST'])
def upload_material():
    data = request.get_json()
    if not data or 'username' not in data or 'password' not in data or 'materials' not in data:
        return jsonify({"error": "Request tidak valid"}), 400

    username = data['username']
    password = data['password']
    materials_data = data['materials']
    results = []

    for index, material in enumerate(materials_data):
        conn = None
        material_code_for_log = material.get('Material', f'Row {index+1}')
        try:
            conn = connect_sap_with_credentials(username, password)
            if not conn:
                results.append({ "material_code": material_code_for_log, "status": "Failed", "message": "Connection to SAP failed for this item." })
                continue
            def get_numeric(key, default='0'):
                value = str(material.get(key, '')).strip()
                return value if value and value.lower() != 'none' else default
            def get_string(key):
                value = str(material.get(key, ''))
                return '' if value.lower() == 'none' else value
            rfc_params = {
                'MATERIAL_LONG': get_string('Material'), 'IND_SECTOR': get_string('Industry Sector'), 'OLD_MAT_NO': get_string('Old material number'),
                'MATL_TYPE': get_string('Material Type'), 'MATL_GROUP': get_string('Material Group'), 'BASE_UOM': get_string('Base Unit of Measure'),
                'MATL_DESC': get_string('Material Description'), 'DIVISION': get_string('Division'), 'ITEM_CAT': get_string('General item cat group'),
                'BASIC_MATL': get_string('Prod./insp. Memo'), 'DOCUMENT': get_string('Document'), 'STD_DESCR': get_string('Ind. Std Desc'),
                'SIZE_DIM': get_string('Dimension'), 'PLANT': get_string('Plant'), 'STGE_LOC': get_string('Storage Location'), 'SALES_ORG': get_string('Sales Organization'),
                'DISTR_CHAN': get_string('Distribution Channel'), 'DELYG_PLNT': get_string('Delivery Plant'), 'SALES_UNIT_ISO': get_string('Sales Unit'),
                'COUNTRYORI': get_string('Tax Country'), 'TAXCLASS_1': get_string('Tax Class'), 'TAX_TYPE_1': get_string('Tax Cat'), 'MTPOS': get_string('Item Category Group'),
                'ACCT_ASSGT': get_string('Acct assignment grp'), 'MATL_GRP_1': get_string('Mat Group 1'), 'MATL_GRP_2': get_string('Mat Group 2'), 'MATL_GRP_3': get_string('Mat Group 3'),
                'MATL_GRP_4': get_string('Mat Group 4'), 'MATL_GRP_5': get_string('Mat Group 5'), 'TRANS_GRP': get_string('Trans Group'), 'LOADINGGRP': get_string('Loadin Group'),
                'MAT_GRP_SM': get_string('Material Package'), 'SH_MAT_TYP': get_string('Mat pack type'), 'BATCH_MGMT': get_string('Batch Management'), 'PROFIT_CTR': get_string('Profit Center'),
                'VAL_CLASS': get_string('Valuation Class'), 'PRICE_CTRL': get_string('Price Control'), 'ALT_UNIT': get_string('Alternative UoM'), 'UNIT_DIM_ISO': get_string('Unit of Dimension'),
                'UNIT_OF_WT_ISO': get_string('Weight Unit'), 'PUR_GROUP': get_string('Purchasing Group'), 'VOLUMEUNIT_ISO': get_string('Volume Unit'), 'UOMUSAGE': get_string('Proportion unit'),
                'XCLASS_NUM': get_string('Class'), 'WARNA': get_string('WARNA '), 'VOL_PROD': get_string('VOLUME PRODUCT'), 'MRP_TYPE': get_string('MRP Type'), 'MRP_GROUP': get_string('MRP GROUP'),
                'MRP_CTRLER': get_string('MRP Controller'), 'LOTSIZEKEY': get_string('Lot Size'), 'PROC_TYPE': get_string('Procurement Type'), 'SPPROCTYPE': get_string('Special Procurement Type'),
                'BACKFLUSH': get_string('Backflush Indicator'), 'INHSEPRODT': get_string('Inhouse Production'), 'SM_KEY': get_string('Schedulled Margin Key'), 'PLAN_STRGP': get_string('Strategy Group'),
                'CONSUMMODE': get_string('Consumption Mode'), 'PERIOD_IND': get_string('period indicator'), 'FY_VARIANT': get_string('fiscal year'), 'AVAILCHECK': get_string('Availability Check'),
                'ALT_BOM_ID': get_string('Selection Method'), 'DEP_REQ_ID': get_string('Individual Collective'), 'ISSUE_UNIT_ISO': get_string('Unit Of Issue'), 'ISS_ST_LOC': get_string('Production Storage Location'),
                'SLOC_EXPRC': get_string('Storage loc. for EP'), 'PRODPROF': get_string('Prod Schedule Profile'), 'UNLIMITED': get_string('Unlt Deliv Tol'), 'ORIG_MAT': get_string('Material-related origin'),
                'QTY_STRUCT': get_string('Ind Qty Structure'), 'NO_COSTING': get_string('Do Not Cost'), 'PUR_STATUS': get_string('Plant-sp.matl status'), 'DETERM_GRP': get_string('Stock Determination Group'),
                'EXTMATLGRP': get_string('Unnamed: 95'), 'INSPTYPE': get_string('Inspection Type'),
                'STD_PRICE': get_numeric('StandardPrc'), 'MOVING_PR': get_numeric('MovingAvg'), 'PRICE_UNIT': get_numeric('Price Unit', default='1'), 'PEINH_2': get_numeric('Price Unit Hard Currency', default='1'),
                'DENOMINATR': get_numeric('Denominator', default='1'), 'NUMERATOR': get_numeric('Numerator', default='1'), 'LENGTH': get_numeric('Length'), 'WIDTH': get_numeric('Width'), 'HEIGHT': get_numeric('Height'),
                'GROSS_WT': get_numeric('Gross Weight'), 'NET_WEIGHT': get_numeric('Net Weight'), 'VOLUME': get_numeric('Volume'), 'MINLOTSIZE': get_numeric('Min Lot Size'), 'MAXLOTSIZE': get_numeric('Max Lot Size'),
                'ROUND_VAL': get_numeric('Rounding Value'), 'PLND_DELRY': get_numeric('Pl. Deliv. Time'), 'GR_PR_TIME': get_numeric('GR Processing Time'), 'SAFETY_STK': get_numeric('Safety Stock'),
                'FWD_CONS': get_numeric('Forward Consumption Period'), 'BWD_CONS': get_numeric('Backward Consumption Period'), 'UNDER_TOL': get_numeric('Under Delivery Tolerance'), 'OVER_TOL': get_numeric('Over Delivery Tolerance'),
                'LOT_SIZE': get_numeric('Costing lot size'),
            }
            result = conn.call('Z_RFC_UPL_MATERIAL', **rfc_params)
            results.append({
                "material_code": get_string('Material'), "plant": get_string('Plant'),
                "status": "Success", "message": "OK"
            })
        except ABAPApplicationError as e:
            results.append({ "material_code": material_code_for_log, "status": "Failed", "message": e.message })
        except Exception as e:
            results.append({ "material_code": material_code_for_log, "status": "Failed", "message": str(e) })
        finally:
            if conn:
                conn.close()
    return jsonify({ "status": "success", "message": "Upload to SAP completed successfully.", "results": results })

@app.route('/activate_qm', methods=['POST'])
def activate_qm():
    data = request.get_json()
    if not all(k in data for k in ['username', 'password', 'materials']):
        return jsonify({"error": "Request tidak valid."}), 400
    username = data['username']
    password = data['password']
    materials_to_activate = data['materials']
    results = []
    for item in materials_to_activate:
        conn = None
        matnr = item.get('matnr')
        werks = item.get('werks')
        try:
            time.sleep(1)
            conn = connect_sap_with_credentials(username, password)
            if not conn:
                results.append({"material_code": matnr, "status": "Failed", "message": "Connection failed for this item."})
                continue
            matnr_padded = str(matnr).zfill(18)
            result = conn.call('Z_RFC_ACTV_QM', IV_MATNR=matnr_padded, IV_WERKS=werks, IV_INSPTYPE='04')
            message = result.get('EX_RETURN_MSG', '').strip()
            if message == 'Inspection type successfully updated':
                results.append({"material_code": matnr, "status": "Success", "message": message})
            else:
                failure_message = message if message else "No specific error message returned from SAP."
                results.append({"material_code": matnr, "status": "Failed", "message": failure_message})
        except Exception as e:
            results.append({"material_code": matnr, "status": "Failed", "message": str(e)})
        finally:
            if conn:
                conn.close()
    return jsonify({ "status": "success", "message": "QM activation process finished.", "results": results })


if __name__ == '__main__':
    # Menjalankan aplikasi di semua interface agar bisa diakses dari luar container/VM
    app.run(host='0.0.0.0', port=5001, debug=True)

