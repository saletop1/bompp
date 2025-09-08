import os
from flask import Flask, request, jsonify
from pyrfc import Connection, ABAPApplicationError, CommunicationError
import re
import time

app = Flask(__name__)

# Fungsi koneksi tidak berubah
def connect_sap_with_credentials(user, passwd):
    """Membuka koneksi ke SAP menggunakan kredensial yang diberikan."""
    try:
        return Connection(user=user, passwd=passwd, ashost="192.168.254.154", sysnr="01", client="300", lang="EN")
    except Exception as e:
        print(f"Error connecting to SAP: {e}")
        return None

# Fungsi increment tidak berubah
def increment_material_code(code):
    """Menghitung kode material berikutnya."""
    match = re.search(r'(.*\D)?(\d+)$', code)
    if not match: return f"{code}-1"
    prefix, number_str = match.groups()
    prefix = prefix or ''
    number = int(number_str)
    padding = len(number_str)
    return f"{prefix}{str(number + 1).zfill(padding)}"

# Endpoint get_next_material tidak berubah
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

# Endpoint untuk proses upload material ke SAP
@app.route('/upload_material', methods=['POST'])
def upload_material():
    data = request.get_json()
    if not data or 'username' not in data or 'password' not in data or 'materials' not in data:
        return jsonify({"error": "Request tidak valid"}), 400

    username = data['username']
    password = data['password']
    materials_data = data['materials']
    print(f"Mencoba upload {len(materials_data)} material untuk user: {username}")

    results = []
    # Strategi: Buka-tutup koneksi untuk setiap item
    for index, material in enumerate(materials_data):
        conn = None
        material_code_for_log = material.get('Material', f'Row {index+1}')
        try:
            print(f"  - Membuka koneksi untuk material: {material_code_for_log}...")
            conn = connect_sap_with_credentials(username, password)
            if not conn:
                results.append({ "material_code": material_code_for_log, "status": "Failed", "message": "Connection to SAP failed for this item." })
                continue

            # Fungsi helper untuk membersihkan data
            def get_numeric(key, default='0'):
                value = str(material.get(key, '')).strip()
                return value if value and value.lower() != 'none' else default
            def get_string(key):
                value = str(material.get(key, ''))
                return '' if value.lower() == 'none' else value

            # Mapping rfc_params lengkap
            rfc_params = {
                'MATERIAL_LONG': get_string('Material'),
                'IND_SECTOR': get_string('Industry Sector'),
                'OLD_MAT_NO': get_string('Old material number'),
                'MATL_TYPE': get_string('Material Type'),
                'MATL_GROUP': get_string('Material Group'),
                'BASE_UOM': get_string('Base Unit of Measure'),
                'MATL_DESC': get_string('Material Description'),
                'DIVISION': get_string('Division'),
                'ITEM_CAT': get_string('General item cat group'),
                'BASIC_MATL': get_string('Prod./insp. Memo'),
                'DOCUMENT': get_string('Document'),
                'STD_DESCR': get_string('Ind. Std Desc'),
                'SIZE_DIM': get_string('Dimension'),
                'PLANT': get_string('Plant'),
                'STGE_LOC': get_string('Storage Location'),
                'SALES_ORG': get_string('Sales Organization'),
                'DISTR_CHAN': get_string('Distribution Channel'),
                'DELYG_PLNT': get_string('Delivery Plant'),
                'SALES_UNIT_ISO': get_string('Sales Unit'),
                'COUNTRYORI': get_string('Tax Country'),
                'TAXCLASS_1': get_string('Tax Class'),
                'TAX_TYPE_1': get_string('Tax Cat'),
                'MTPOS': get_string('Item Category Group'),
                'ACCT_ASSGT': get_string('Acct assignment grp'),
                'MATL_GRP_1': get_string('Mat Group 1'),
                'MATL_GRP_2': get_string('Mat Group 2'),
                'MATL_GRP_3': get_string('Mat Group 3'),
                'MATL_GRP_4': get_string('Mat Group 4'),
                'MATL_GRP_5': get_string('Mat Group 5'),
                'TRANS_GRP': get_string('Trans Group'),
                'LOADINGGRP': get_string('Loadin Group'),
                'MAT_GRP_SM': get_string('Material Package'),
                'SH_MAT_TYP': get_string('Mat pack type'),
                'BATCH_MGMT': get_string('Batch Management'),
                'PROFIT_CTR': get_string('Profit Center'),
                'VAL_CLASS': get_string('Valuation Class'),
                'PRICE_CTRL': get_string('Price Control'),
                'ALT_UNIT': get_string('Alternative UoM'),
                'UNIT_DIM_ISO': get_string('Unit of Dimension'),
                'UNIT_OF_WT_ISO': get_string('Weight Unit'),
                'PUR_GROUP': get_string('Purchasing Group'),
                'VOLUMEUNIT_ISO': get_string('Volume Unit'),
                'UOMUSAGE': get_string('Proportion unit'),
                'XCLASS_NUM': get_string('Class'),
                'WARNA': get_string('WARNA '),
                'VOL_PROD': get_string('VOLUME PRODUCT'),
                'MRP_TYPE': get_string('MRP Type'),
                'MRP_GROUP': get_string('MRP GROUP'),
                'MRP_CTRLER': get_string('MRP Controller'),
                'LOTSIZEKEY': get_string('Lot Size'),
                'PROC_TYPE': get_string('Procurement Type'),
                'SPPROCTYPE': get_string('Special Procurement Type'),
                'BACKFLUSH': get_string('Backflush Indicator'),
                'INHSEPRODT': get_string('Inhouse Production'),
                'SM_KEY': get_string('Schedulled Margin Key'),
                'PLAN_STRGP': get_string('Strategy Group'),
                'CONSUMMODE': get_string('Consumption Mode'),
                'PERIOD_IND': get_string('period indicator'),
                'FY_VARIANT': get_string('fiscal year'),
                'AVAILCHECK': get_string('Availability Check'),
                'ALT_BOM_ID': get_string('Selection Method'),
                'DEP_REQ_ID': get_string('Individual Collective'),
                'ISSUE_UNIT_ISO': get_string('Unit Of Issue'),
                'ISS_ST_LOC': get_string('Production Storage Location'),
                'SLOC_EXPRC': get_string('Storage loc. for EP'),
                'PRODPROF': get_string('Prod Schedule Profile'),
                'UNLIMITED': get_string('Unlt Deliv Tol'),
                'ORIG_MAT': get_string('Material-related origin'),
                'QTY_STRUCT': get_string('Ind Qty Structure'),
                'NO_COSTING': get_string('Do Not Cost'),
                'PUR_STATUS': get_string('Plant-sp.matl status'),
                'DETERM_GRP': get_string('Stock Determination Group'),
                'EXTMATLGRP': get_string('Unnamed: 95'),
                'INSPTYPE': get_string('Inspection Type'),
                'STD_PRICE': get_numeric('StandardPrc'),
                'MOVING_PR': get_numeric('MovingAvg'),
                'PRICE_UNIT': get_numeric('Price Unit', default='1'),
                'PEINH_2': get_numeric('Price Unit Hard Currency', default='1'),
                'DENOMINATR': get_numeric('Denominator', default='1'),
                'NUMERATOR': get_numeric('Numerator', default='1'),
                'LENGTH': get_numeric('Length'),
                'WIDTH': get_numeric('Width'),
                'HEIGHT': get_numeric('Height'),
                'GROSS_WT': get_numeric('Gross Weight'),
                'NET_WEIGHT': get_numeric('Net Weight'),
                'VOLUME': get_numeric('Volume'),
                'MINLOTSIZE': get_numeric('Min Lot Size'),
                'MAXLOTSIZE': get_numeric('Max Lot Size'),
                'ROUND_VAL': get_numeric('Rounding Value'),
                'PLND_DELRY': get_numeric('Pl. Deliv. Time'),
                'GR_PR_TIME': get_numeric('GR Processing Time'),
                'SAFETY_STK': get_numeric('Safety Stock'),
                'FWD_CONS': get_numeric('Forward Consumption Period'),
                'BWD_CONS': get_numeric('Backward Consumption Period'),
                'UNDER_TOL': get_numeric('Under Delivery Tolerance'),
                'OVER_TOL': get_numeric('Over Delivery Tolerance'),
                'LOT_SIZE': get_numeric('Costing lot size'),
            }

            result = conn.call('Z_RFC_UPL_MATERIAL', **rfc_params)
            results.append({
                "material_code": get_string('Material'),
                "plant": get_string('Plant'),
                "status": "Success",
                "message": "OK"
            })

        except ABAPApplicationError as e:
            results.append({ "material_code": material_code_for_log, "status": "Failed", "message": e.message })
        except Exception as e:
            results.append({ "material_code": material_code_for_log, "status": "Failed", "message": str(e) })
        finally:
            if conn:
                print(f"  - Menutup koneksi untuk material: {material_code_for_log}.")
                conn.close()

    return jsonify({ "status": "success", "message": "Upload process finished.", "results": results })


@app.route('/activate_qm', methods=['POST'])
def activate_qm():
    data = request.get_json()
    if not all(k in data for k in ['username', 'password', 'materials']):
        return jsonify({"error": "Request tidak valid. 'username', 'password', dan 'materials' dibutuhkan."}), 400

    username = data['username']
    password = data['password']
    materials_to_activate = data['materials']

    print(f"Mencoba aktivasi QM untuk {len(materials_to_activate)} material")

    results = []
    # Strategi: Buka-tutup koneksi untuk setiap item
    for item in materials_to_activate:
        conn = None
        matnr = item.get('matnr')
        werks = item.get('werks')
        try:
            print(f"  - Membuka koneksi untuk aktivasi QM material: {matnr}...")
            conn = connect_sap_with_credentials(username, password)
            if not conn:
                results.append({"material_code": matnr, "status": "Failed", "message": "Connection failed for this item."})
                continue

            # --- PERBAIKAN: Format material menjadi 18 digit dengan leading zero ---
            # zfill(18) akan otomatis menambahkan nol di depan hingga total panjangnya 18 karakter
            matnr_padded = str(matnr).zfill(18)
            print(f"    -> Mengirim kode: {matnr_padded}")
            # ---------------------------------------------------------------------

            # Panggil RFC dengan kode material yang sudah diformat
            result = conn.call('Z_RFC_ACTV_QM', IV_MATNR=matnr_padded, IV_WERKS=werks, IV_INSPTYPE='04')

            message = result.get('EX_RETURN_MSG', '').strip()

            expected_success_message = 'Inspection type successfully updated'
            if message == expected_success_message:
                 results.append({"material_code": matnr, "status": "Success", "message": message})
            else:
                 failure_message = message if message else "No specific error message returned from SAP."
                 results.append({"material_code": matnr, "status": "Failed", "message": failure_message})

        except ABAPApplicationError as e:
            print(f"    -> SAP Error for {matnr}: {e.message}")
            results.append({"material_code": matnr, "status": "Failed", "message": e.message})
        except Exception as e:
            print(f"    -> Generic Error for {matnr}: {str(e)}")
            results.append({"material_code": matnr, "status": "Failed", "message": str(e)})
        finally:
            if conn:
                print(f"  - Menutup koneksi untuk aktivasi QM material: {matnr}.")
                conn.close()

    return jsonify({
        "status": "success",
        "message": "QM activation process finished.",
        "results": results
    })


if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5001)

