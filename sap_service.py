import os
from flask import Flask, request, jsonify
from pyrfc import Connection, ABAPApplicationError
import re
import time
import logging
from datetime import datetime

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
    """
    Memformat kode material untuk SAP dengan membersihkan data input.
    - Jika kode berisi huruf (alphanumeric), hapus semua leading zero.
    - Jika kode hanya berisi angka (numeric), tambahkan padding zero hingga 18 karakter.
    """
    if not code:
        return ''
    code_str = str(code).strip()

    # Cek apakah ada huruf di dalam string
    if not code_str.isdigit():
        # Jika ada huruf, ini adalah kode alfanumerik. Hapus leading zero.
        return code_str.lstrip('0')
    else:
        # Jika hanya angka, ini adalah kode numerik. Tambahkan padding.
        return code_str.zfill(18)

@app.route('/stage_materials', methods=['POST'])
def stage_materials():
    data = request.get_json()
    if not data or 'materials' not in data:
        return jsonify({"error": "Request tidak valid, 'materials' dibutuhkan"}), 400

    materials_data = data['materials']
    staged_results = []
    for material in materials_data:
        staged_results.append({
            'Material': material.get('Material'),
            'Material Description': material.get('Material Description'),
            'original_data': material
        })

    return jsonify({
        "status": "staged",
        "message": "Materials staged for activation.",
        "results": staged_results
    })

@app.route('/upload_bom', methods=['POST'])
def upload_bom():
    data = request.get_json()
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
        parent_material = bom.get('IV_MATNR')
        logging.info(f"\n--- 2. Processing BOM #{i+1} for Parent: {parent_material} ---")
        try:
            conn = connect_sap_with_credentials(username, password)
            if not conn:
                results.append({"material_code": parent_material, "status": "Failed", "message": "Connection to SAP failed."})
                continue
            def format_quantity_for_sap(qty_val):
                try:
                    numeric_val = float(str(qty_val).replace(',', '.'))
                except (ValueError, TypeError):
                    numeric_val = 0.0
                if numeric_val == int(numeric_val):
                    return str(int(numeric_val))
                else:
                    rounded_val = round(numeric_val, 3)
                    return f"{rounded_val:.3f}".replace('.', ',')
            base_quantity_str = format_quantity_for_sap(bom.get('IV_BMENG', 1))
            formatted_components = []
            for comp in bom.get('IT_COMPONENTS', []):
                comp_qty_str = format_quantity_for_sap(comp.get('COMP_QTY', 0))
                formatted_components.append({
                    'ITEM_CATEG': str(comp.get('ITEM_CATEG', 'L')), 'POSNR': str(comp.get('POSNR', '')),
                    'COMPONENT': format_material_code_for_sap(comp.get('COMPONENT', '')),
                    'COMP_QTY': comp_qty_str, 'COMP_UNIT': str(comp.get('COMP_UNIT', '')),
                    'PROD_STOR_LOC': str(comp.get('PROD_STOR_LOC', '')), 'SCRAP': str(comp.get('SCRAP', '0')),
                    'ITEM_TEXT': str(comp.get('ITEM_TEXT', '')), 'ITEM_TEXT2': str(comp.get('ITEM_TEXT2', '')),
                })
            rfc_params = {
                'IV_MATNR': format_material_code_for_sap(bom.get('IV_MATNR', '')), 'IV_WERKS': str(bom.get('IV_WERKS', '')),
                'IV_STLAN': str(bom.get('IV_STLAN', '')), 'IV_STLAL': str(bom.get('IV_STLAL', '')),
                'IV_DATUV': str(bom.get('IV_DATUV', '')), 'IV_BMENG': base_quantity_str,
                'IV_BMEIN': str(bom.get('IV_BMEIN', '')), 'IV_STKTX': str(bom.get('IV_STKTX', '')),
                'IT_COMPONENTS': formatted_components
            }
            logging.info("--- 3. Parameters being sent to SAP RFC ---")
            print(rfc_params)
            logging.info("-----------------------------------------")
            result = conn.call('Z_RFC_UPLOAD_BOM_CS01', **rfc_params)
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

@app.route('/upload_material_legacy', methods=['POST'])
def upload_material_legacy():
    return jsonify({"status":"legacy", "message": "This endpoint is deprecated"})

@app.route('/activate_qm_and_upload', methods=['POST'])
def activate_qm_and_upload():
    data = request.get_json()
    if not all(k in data for k in ['username', 'password', 'materials']):
        return jsonify({"error": "Request tidak valid."}), 400

    username = data['username']
    password = data['password']
    materials_to_process = [item['original_data'] for item in data['materials']]
    results = []

    conn = None
    try:
        conn = connect_sap_with_credentials(username, password)
        if not conn:
            return jsonify({"error": "Koneksi ke SAP gagal", "status": "failed"}), 500

        for material in materials_to_process:
            material_code_for_log = material.get('Material', 'N/A')
            try:
                def get_string(key):
                    value = str(material.get(key, ''))
                    return '' if value.lower() == 'none' else value
                def get_numeric(key, default='0'):
                    value = str(material.get(key, '')).strip()
                    return value if value and value.lower() != 'none' else default

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
                    'GROSS_WT': get_numeric('Gross Weight'), 'NET_WEIGHT': get_string('Net Weight'), 'VOLUME': get_numeric('Volume'), 'MINLOTSIZE': get_numeric('Min Lot Size'), 'MAXLOTSIZE': get_string('Max Lot Size'),
                    'ROUND_VAL': get_numeric('Rounding Value'), 'PLND_DELRY': get_numeric('Pl. Deliv. Time'), 'GR_PR_TIME': get_numeric('GR Processing Time'), 'SAFETY_STK': get_numeric('Safety Stock'),
                    'FWD_CONS': get_numeric('Forward Consumption Period'), 'BWD_CONS': get_string('Backward Consumption Period'), 'UNDER_TOL': get_numeric('Under Delivery Tolerance'), 'OVER_TOL': get_numeric('Over Delivery Tolerance'),
                    'LOT_SIZE': get_numeric('Costing lot size'),
                }
                conn.call('Z_RFC_UPL_MATERIAL', **rfc_params)
                logging.info(f"Material {material_code_for_log} uploaded successfully.")

                time.sleep(1)
                matnr = get_string('Material')
                werks = get_string('Plant')
                matnr_padded = format_material_code_for_sap(matnr)
                mat_desc = get_string('Material Description')
                base_uom = get_string('Base Unit of Measure')

                qm_result = conn.call('Z_RFC_ACTV_QM', IV_MATNR=matnr_padded, IV_WERKS=werks, IV_INSPTYPE='04')
                message = qm_result.get('EX_RETURN_MSG', '').strip()

                if message == 'Inspection type successfully updated':
                    results.append({
                        "material_code": matnr,
                        "status": "Success",
                        "message": "Material Created & QM Activated.",
                        "description": mat_desc,
                        "plant": werks,
                        "base_uom": base_uom
                    })
                else:
                    failure_message = f"Material created, but QM activation failed: {message}" if message else "Material created, but QM activation failed with no specific error."
                    results.append({"material_code": matnr, "status": "Failed", "message": failure_message})

            except ABAPApplicationError as e:
                results.append({ "material_code": material_code_for_log, "status": "Failed", "message": f"Upload/Activation Error: {e.message}" })
            except Exception as e:
                results.append({ "material_code": material_code_for_log, "status": "Failed", "message": f"General Error: {str(e)}" })

    finally:
        if conn:
            conn.close()

    return jsonify({ "status": "success", "message": "Material processing finished.", "results": results })


# --- Endpoint untuk Inspection Plan ---
@app.route('/create_inspection_plan', methods=['POST'])
def create_inspection_plan():
    data = request.get_json()
    if not all(k in data for k in ['username', 'password', 'materials', 'plan_details']):
        return jsonify({"error": "Request tidak valid. 'username', 'password', 'materials', dan 'plan_details' dibutuhkan."}), 400

    username = data['username']
    password = data['password']
    materials = data['materials']
    plan_details = data['plan_details']
    results = []

    conn = None
    try:
        conn = connect_sap_with_credentials(username, password)
        if not conn:
            return jsonify({"error": "Koneksi ke SAP gagal", "status": "failed"}), 500

        for material in materials:
            mat_code = material.get('material_code')
            mat_desc = material.get('description', f"Inspection for {mat_code}")
            plant = material.get('plant')
            base_uom = material.get('base_uom', 'PC')

            if not all([mat_code, plant]):
                results.append({"material_code": mat_code or "N/A", "status": "Skipped", "message": "Material code or plant is missing."})
                continue

            try:
                method_val, char_descr_val = '', ''
                plant_str = str(plant)
                if plant_str == '3000':
                    method_val, char_descr_val = 'MIC00005', 'MIC PRODUCT SMG'
                elif plant_str == '2000':
                    method_val, char_descr_val = 'MIC00002', 'MIC PRODUCT SBY'
                elif plant_str == '1000':
                    method_val, char_descr_val = 'MIC00001', 'MIC KMI 1 SURABAYA'
                elif plant_str == '1001':
                    method_val, char_descr_val = 'MIC00003', 'MIC PRODUCT SBY PLANT 1001'
                else:
                    method_val, char_descr_val = 'MIC00005', f"CHECK 0010 FOR PLANT {plant_str}"

                task_desc_val = str(mat_desc[:40])

                rfc_params = {
                    'IM_VALID_FROM': datetime.now().strftime('%Y%m%d'),
                    'IM_TASK_USAGE': str(plan_details.get('task_usage', '5')),
                    'IM_TASK_STATUS': str(plan_details.get('task_status', '4')),
                    'IM_TASK_UNIT': str(base_uom),
                    'IM_LOT_SIZE_TO': str('9999999999'),
                    'IM_TASK_DESC': task_desc_val,
                    'IM_MATERIAL': format_material_code_for_sap(mat_code),
                    'IM_PLANT': str(plant),
                    'IM_ACTIVITY': str('0010'),
                    'IM_CONTROL_KEY': str(plan_details.get('control_key', 'QM01')),
                    'IM_OPERATION_DESC': task_desc_val,
                    'IM_OPERATION_MEASURE_UNIT': str(base_uom),
                    'IM_BASE_QTY': 1.0,
                    'IM_SMPL_QUANT': 1.0,
                    'IM_INSPCHAR': '0010',
                    'IM_CHAR_DESCR': str(char_descr_val[:40]).upper(),
                    'IM_METHOD': str(method_val),
                    'IM_PMETHOD': str(plant),
                    'IM_SMPL_PROCEDURE': str('SAMP0001'),
                    'IM_SMPL_UNIT': str(base_uom)
                }

                logging.info(f"Calling ZRFC_CREATE_INSPECTION_PLAN for Material: {mat_code} in Plant: {plant}")
                logging.info(f"Parameters: {rfc_params}")
                result = conn.call('ZRFC_CREATE_INSPECTION_PLAN', **rfc_params)
                logging.info(f"Result from SAP: {result}")

                return_msg = result.get('EX_RETURN', {})
                msg_type = return_msg.get('TYPE', '')
                message = return_msg.get('MESSAGE', '').strip()

                if msg_type == 'S' or not msg_type:
                     results.append({
                        "material_code": mat_code,
                        "status": "Success",
                        "message": f"Inspection Plan created. Group: {result.get('EX_GROUP')} / Cnt: {result.get('EX_GROUP_COUNTER')}"
                    })
                else:
                    if not message:
                        error_parts = [
                            return_msg.get('MESSAGE_V1', ''), return_msg.get('MESSAGE_V2', ''),
                            return_msg.get('MESSAGE_V3', ''), return_msg.get('MESSAGE_V4', '')
                        ]
                        full_error = ' '.join(filter(None, error_parts))
                        message = f"Undescribed error from SAP. Details: [{full_error.strip()}]"
                    results.append({"material_code": mat_code, "status": "Failed", "message": f"SAP Error: {message}"})

            except ABAPApplicationError as e:
                logging.error(f"ABAP Error for {mat_code}: {e.message}")
                results.append({ "material_code": mat_code, "status": "Failed", "message": f"ABAP Error: {e.message}" })
            except Exception as e:
                logging.error(f"General Error for {mat_code}: {str(e)}")
                results.append({ "material_code": mat_code, "status": "Failed", "message": f"General Error: {str(e)}" })

    finally:
        if conn:
            conn.close()

    return jsonify({"status": "success", "message": "Inspection plan creation process finished.", "results": results})


if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5001, debug=True)
