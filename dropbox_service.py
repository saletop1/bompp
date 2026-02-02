import os
import sys
import json
import logging
import re
import time
import secrets
import webbrowser
import socket
from datetime import datetime, timedelta
from typing import Dict, List, Optional, Any, Tuple
from functools import wraps

# Load environment variables FIRST
from dotenv import load_dotenv
load_dotenv()

# ==================== AUTO-LOAD TOKENS FROM JSON ====================

def auto_load_dropbox_tokens():
    """Automatically load Dropbox tokens from JSON file to environment"""
    TOKEN_FILE = 'dropbox_tokens.json'
    
    try:
        if os.path.exists(TOKEN_FILE):
            with open(TOKEN_FILE, 'r') as f:
                tokens = json.load(f)
            
            # Load access token
            if 'access_token' in tokens and tokens['access_token']:
                os.environ['DROPBOX_ACCESS_TOKEN'] = tokens['access_token']
                print(f"[AUTO-LOAD] Loaded DROPBOX_ACCESS_TOKEN from {TOKEN_FILE}")
            
            # Load refresh token
            if 'refresh_token' in tokens and tokens['refresh_token']:
                os.environ['DROPBOX_REFRESH_TOKEN'] = tokens['refresh_token']
                print(f"[AUTO-LOAD] Loaded DROPBOX_REFRESH_TOKEN from {TOKEN_FILE}")
            
            return True
        else:
            print(f"[AUTO-LOAD] {TOKEN_FILE} not found, using .env tokens")
            return False
    except Exception as e:
        print(f"[AUTO-LOAD] Error loading tokens: {e}")
        return False

# Execute auto-load
auto_load_dropbox_tokens()

# ==================== DROPBOX IMPORTS ====================

import dropbox
from dropbox import DropboxOAuth2FlowNoRedirect
from dropbox.files import WriteMode, FileMetadata, FolderMetadata
from dropbox.exceptions import ApiError, AuthError, DropboxException

# Flask imports
from flask import Flask, request, jsonify, send_file, session
from flask_cors import CORS

# SAP RFC imports (using pyrfc) - with better error handling
try:
    from pyrfc import Connection, RFCError
    PYRFC_AVAILABLE = True
except ImportError as e:
    print(f"Warning: pyrfc module not available. SAP functionality will be disabled. Error: {e}")
    PYRFC_AVAILABLE = False
    # Create dummy classes for when pyrfc is not available
    class RFCError(Exception):
        pass
    class Connection:
        def __init__(self, **kwargs):
            pass
        def call(self, *args, **kwargs):
            return {}

# Database imports (MySQL)
try:
    import pymysql
    from pymysql import MySQLError
    PYMYSQL_AVAILABLE = True
except ImportError:
    print("Warning: pymysql module not available. Database functionality will be disabled.")
    PYMYSQL_AVAILABLE = False

# Configure logging - FIX untuk Windows
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('dropbox_service.log', encoding='utf-8'),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger(__name__)

# ==================== CONFIGURATION ====================

# Configuration from environment variables (now includes auto-loaded tokens)
DROPBOX_APP_KEY = os.getenv('DROPBOX_APP_KEY')
DROPBOX_APP_SECRET = os.getenv('DROPBOX_APP_SECRET')
DROPBOX_REFRESH_TOKEN = os.getenv('DROPBOX_REFRESH_TOKEN')
DROPBOX_ACCESS_TOKEN = os.getenv('DROPBOX_ACCESS_TOKEN')

# SAP Configuration
SAP_ASHOST = os.getenv('SAP_ASHOST', '192.168.254.154')
SAP_SYSNR = os.getenv('SAP_SYSNR', '01')
SAP_CLIENT = os.getenv('SAP_CLIENT', '300')
SAP_USER = os.getenv('SAP_USER', 'auto_email')
SAP_PASSWD = os.getenv('SAP_PASSWD', '11223344')
SAP_LANG = os.getenv('SAP_LANG', 'EN')

# API Authentication
API_TOKEN = os.getenv('API_TOKEN', '9nQ0qj0cK3yfX6pR8tW2vS5zA7dF1gH4jL9mN0qB3vC6xZ8')

# Database Configuration
DB_HOST = os.getenv('DB_HOST', '127.0.0.1')
DB_PORT = int(os.getenv('DB_PORT', 3306))
DB_DATABASE = os.getenv('DB_DATABASE', 'db_bompp')
DB_USERNAME = os.getenv('DB_USERNAME', 'root')
DB_PASSWORD = os.getenv('DB_PASSWORD', '')

# Token storage file
TOKEN_FILE = 'dropbox_tokens.json'
SECRET_KEY_FILE = 'flask_secret.key'

# ==================== DATABASE HELPER ====================

class DatabaseHelper:
    """Helper class for database operations (READ ONLY - No INSERT/UPDATE/DELETE)"""
    
    @staticmethod
    def get_connection():
        """Get database connection"""
        if not PYMYSQL_AVAILABLE:
            logger.error("pymysql not available. Database functionality disabled.")
            return None
        
        try:
            connection = pymysql.connect(
                host=DB_HOST,
                port=DB_PORT,
                user=DB_USERNAME,
                password=DB_PASSWORD,
                database=DB_DATABASE,
                charset='utf8mb4',
                cursorclass=pymysql.cursors.DictCursor,
                connect_timeout=10
            )
            return connection
        except Exception as e:
            logger.error(f"Database connection error: {e}")
            return None
    
    @staticmethod
    def get_shop_drawings_from_db(material_code: str, plant: str = None) -> List[Dict[str, Any]]:
        """Get shop drawings from database"""
        drawings = []
        
        try:
            connection = DatabaseHelper.get_connection()
            if not connection:
                return drawings
            
            with connection.cursor() as cursor:
                if plant:
                    sql = """
                    SELECT 
                        id, material_code, plant, description, drawing_type, revision,
                        dropbox_file_id, dropbox_path, dropbox_share_url, dropbox_direct_url,
                        filename, original_filename, file_size, file_extension, user_id, uploaded_at,
                        material_type, material_group, base_unit
                    FROM shop_drawings 
                    WHERE material_code = %s AND plant = %s
                    ORDER BY uploaded_at DESC
                    """
                    cursor.execute(sql, (material_code, plant))
                else:
                    sql = """
                    SELECT 
                        id, material_code, plant, description, drawing_type, revision,
                        dropbox_file_id, dropbox_path, dropbox_share_url, dropbox_direct_url,
                        filename, original_filename, file_size, file_extension, user_id, uploaded_at,
                        material_type, material_group, base_unit
                    FROM shop_drawings 
                    WHERE material_code = %s
                    ORDER BY uploaded_at DESC
                    """
                    cursor.execute(sql, (material_code,))
                
                rows = cursor.fetchall()
                
                for row in rows:
                    drawings.append({
                        'source': 'database',
                        'id': row['id'],
                        'filename': row['filename'],
                        'original_filename': row['original_filename'],
                        'direct_url': row['dropbox_direct_url'],
                        'share_url': row['dropbox_share_url'],
                        'material_code': row['material_code'],
                        'plant': row['plant'],
                        'description': row['description'],
                        'modified': row['uploaded_at'].isoformat() if row['uploaded_at'] else None,
                        'drawing_type': row['drawing_type'],
                        'file_size': row['file_size'],
                        'file_extension': row['file_extension'],
                        'user_id': row['user_id'],
                        'revision': row.get('revision', 'Rev1'),
                        'from_database': True,
                        'material_type': row.get('material_type', 'N/A'),
                        'material_group': row.get('material_group', 'N/A'),
                        'base_unit': row.get('base_unit', 'N/A')
                    })
            
            connection.close()
            logger.info(f"Found {len(drawings)} drawings in database for material: {material_code}")
            
        except Exception as e:
            logger.error(f"Database query error: {e}")
        
        return drawings
    
    @staticmethod
    def get_material_info_from_db(material_code: str) -> Optional[Dict[str, Any]]:
        """Get material information from database (from any drawing record)"""
        try:
            connection = DatabaseHelper.get_connection()
            if not connection:
                return None
            
            with connection.cursor() as cursor:
                sql = """
                SELECT 
                    material_code, description, material_type, material_group, base_unit
                FROM shop_drawings 
                WHERE material_code = %s 
                AND (material_type IS NOT NULL OR material_group IS NOT NULL OR base_unit IS NOT NULL)
                LIMIT 1
                """
                
                cursor.execute(sql, (material_code,))
                row = cursor.fetchone()
            
            connection.close()
            
            if row:
                # Konversi ST ke PC jika diperlukan
                base_unit = row.get('base_unit', 'N/A')
                if base_unit == 'ST':
                    base_unit = 'PC'
                
                return {
                    'material_code': row['material_code'],
                    'description': row.get('description', ''),
                    'material_type': row.get('material_type', 'N/A'),
                    'material_group': row.get('material_group', 'N/A'),
                    'base_unit': base_unit
                }
            else:
                return None
                
        except Exception as e:
            logger.error(f"Error getting material info from database: {e}")
            return None
    
    @staticmethod
    def check_duplicate_drawing(material_code: str, plant: str, drawing_type: str, revision: str) -> bool:
        """Check if a drawing with same material_code, plant, drawing_type and revision already exists"""
        try:
            connection = DatabaseHelper.get_connection()
            if not connection:
                return False
            
            with connection.cursor() as cursor:
                sql = """
                SELECT COUNT(*) as count FROM shop_drawings 
                WHERE material_code = %s 
                AND plant = %s 
                AND drawing_type = %s 
                AND revision = %s
                """
                
                cursor.execute(sql, (material_code, plant, drawing_type, revision))
                result = cursor.fetchone()
            
            connection.close()
            
            return result['count'] > 0 if result else False
                
        except Exception as e:
            logger.error(f"Error checking duplicate drawing: {e}")
            return False

# ==================== SECRET KEY MANAGEMENT ====================

def generate_or_load_secret_key():
    """Generate or load Flask secret key from file"""
    secret_key = None
    
    # Try to load from environment variable first
    secret_key = os.getenv('FLASK_SECRET_KEY')
    if secret_key:
        logger.info("Using secret key from environment variable")
        return secret_key
    
    # Try to load from file
    try:
        if os.path.exists(SECRET_KEY_FILE):
            with open(SECRET_KEY_FILE, 'r') as f:
                secret_key = f.read().strip()
            if secret_key and len(secret_key) >= 16:
                logger.info(f"Loaded secret key from {SECRET_KEY_FILE}")
                return secret_key
    except Exception as e:
        logger.error(f"Error loading secret key from file: {e}")
    
    # Generate new secret key
    secret_key = secrets.token_urlsafe(32)
    logger.info("Generated new secret key")
    
    # Save to file
    try:
        with open(SECRET_KEY_FILE, 'w') as f:
            f.write(secret_key)
        logger.info(f"Saved secret key to {SECRET_KEY_FILE}")
    except Exception as e:
        logger.error(f"Error saving secret key to file: {e}")
    
    return secret_key

# Generate or load secret key
FLASK_SECRET_KEY = generate_or_load_secret_key()

# ==================== FLASK APP INITIALIZATION ====================

# Initialize Flask app
app = Flask(__name__)
app.config['SECRET_KEY'] = FLASK_SECRET_KEY
app.config['SESSION_TYPE'] = 'filesystem'
app.config['PERMANENT_SESSION_LIFETIME'] = timedelta(hours=1)

# Enable CORS
CORS(app)

# Store OAuth state in memory (for development only)
oauth_state = {}

# ==================== TOKEN MANAGER ====================

class TokenManager:
    """Manages Dropbox token storage and refresh"""
    
    def __init__(self):
        self.tokens = self.load_tokens()
    
    def load_tokens(self) -> Dict[str, Any]:
        """Load tokens from file and update environment"""
        try:
            if os.path.exists(TOKEN_FILE):
                with open(TOKEN_FILE, 'r') as f:
                    tokens = json.load(f)
                    logger.info(f"Loaded tokens from {TOKEN_FILE}")
                    
                    # CRITICAL: Update environment variables
                    if tokens.get('access_token'):
                        os.environ['DROPBOX_ACCESS_TOKEN'] = tokens['access_token']
                        logger.info("✓ Updated DROPBOX_ACCESS_TOKEN in environment")
                    
                    if tokens.get('refresh_token'):
                        os.environ['DROPBOX_REFRESH_TOKEN'] = tokens['refresh_token']
                        logger.info("✓ Updated DROPBOX_REFRESH_TOKEN in environment")
                    
                    return tokens
            else:
                logger.warning(f"{TOKEN_FILE} not found, using .env tokens")
        except Exception as e:
            logger.error(f"Error loading tokens: {e}")
        
        # Return default from environment
        return {
            'access_token': os.getenv('DROPBOX_ACCESS_TOKEN'),
            'refresh_token': os.getenv('DROPBOX_REFRESH_TOKEN'),
            'app_key': DROPBOX_APP_KEY,
            'app_secret': DROPBOX_APP_SECRET,
            'last_refresh': datetime.now().isoformat()
        }
    
    def save_tokens(self, tokens: Dict[str, Any]):
        """Save tokens to file and update environment"""
        try:
            with open(TOKEN_FILE, 'w') as f:
                json.dump(tokens, f, indent=2)
            logger.info(f"Saved tokens to {TOKEN_FILE}")
            
            # CRITICAL: Update environment variables
            if tokens.get('access_token'):
                os.environ['DROPBOX_ACCESS_TOKEN'] = tokens['access_token']
                logger.info("✓ Updated DROPBOX_ACCESS_TOKEN in environment")
            
            if tokens.get('refresh_token'):
                os.environ['DROPBOX_REFRESH_TOKEN'] = tokens['refresh_token']
                logger.info("✓ Updated DROPBOX_REFRESH_TOKEN in environment")
        except Exception as e:
            logger.error(f"Error saving tokens: {e}")
    
    def get_access_token(self) -> Optional[str]:
        """Get current access token"""
        return self.tokens.get('access_token')
    
    def get_refresh_token(self) -> Optional[str]:
        """Get current refresh token"""
        return self.tokens.get('refresh_token')
    
    def update_tokens(self, access_token: str = None, refresh_token: str = None):
        """Update tokens with new values"""
        if access_token:
            self.tokens['access_token'] = access_token
        
        if refresh_token:
            self.tokens['refresh_token'] = refresh_token
        
        self.tokens['last_refresh'] = datetime.now().isoformat()
        self.save_tokens(self.tokens)
        logger.info("Tokens updated successfully")
    
    def refresh_access_token(self) -> bool:
        """Refresh access token using refresh token via HTTP request"""
        refresh_token = self.get_refresh_token()
        app_key = DROPBOX_APP_KEY
        app_secret = DROPBOX_APP_SECRET
        
        if not all([refresh_token, app_key, app_secret]):
            logger.error("Missing refresh token or app credentials")
            return False
        
        try:
            logger.info("Refreshing access token via HTTP request...")
            
            import requests
            
            # Make HTTP request to Dropbox API
            url = "https://api.dropboxapi.com/oauth2/token"
            data = {
                'grant_type': 'refresh_token',
                'refresh_token': refresh_token,
                'client_id': app_key,
                'client_secret': app_secret
            }
            
            response = requests.post(url, data=data, timeout=30)
            
            if response.status_code == 200:
                result = response.json()
                new_access_token = result.get('access_token')
                
                if new_access_token:
                    # Update the access token
                    self.update_tokens(access_token=new_access_token)
                    logger.info("Access token refreshed successfully via HTTP")
                    return True
                else:
                    logger.error("No access token in response")
                    return False
            else:
                logger.error(f"Token refresh failed: {response.status_code} - {response.text}")
                
                # If refresh token is invalid, we need to get new tokens
                if response.status_code == 400 and "invalid_grant" in response.text:
                    logger.error("Refresh token is invalid or revoked. Need to get new tokens via OAuth.")
                    self.tokens['refresh_token'] = None
                    self.tokens['access_token'] = None
                    self.save_tokens(self.tokens)
                
                return False
                
        except Exception as e:
            logger.error(f"Failed to refresh access token via HTTP: {e}")
            return False
    
    def has_valid_tokens(self) -> bool:
        """Check if we have valid tokens"""
        return bool(self.get_access_token())
    
    def needs_oauth(self) -> bool:
        """Check if we need to perform OAuth"""
        return not self.has_valid_tokens()

# ==================== SAP MATERIAL VALIDATOR (OPEN/CLOSE CONNECTION) ====================

class SAPMaterialValidator:
    """Validates materials using SAP RFC Z_RFC_GET_MATERIAL_BY_DESC2 - Material Code Only"""
    
    def __init__(self):
        self.connection_status = 'disconnected'
        self.last_connection_attempt = None
        self.connection_error = None
    
    def _test_sap_connection(self):
        """Test if we can reach SAP server"""
        try:
            # Test network connectivity first
            logger.info(f"Testing network connectivity to {SAP_ASHOST}:33{SAP_SYSNR}...")
            
            # Create a socket to test connectivity
            sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            sock.settimeout(5)  # 5 second timeout
            
            # SAP typically uses port 33XX where XX is sysnr
            sap_port = int(f"33{SAP_SYSNR}")
            
            result = sock.connect_ex((SAP_ASHOST, sap_port))
            sock.close()
            
            if result == 0:
                logger.info(f"✓ Network connectivity to {SAP_ASHOST}:{sap_port} is OK")
                return True
            else:
                logger.warning(f"✗ Cannot reach {SAP_ASHOST}:{sap_port}. Error code: {result}")
                logger.warning(f"Please check network connectivity to SAP server {SAP_ASHOST}")
                return False
                
        except Exception as e:
            logger.error(f"Network test error: {e}")
            return False
    
    def _create_connection(self):
        """Create and return a new SAP connection"""
        if not PYRFC_AVAILABLE:
            logger.error("pyrfc module not available. SAP functionality disabled.")
            self.connection_error = "pyrfc module not installed"
            return None
        
        try:
            self.last_connection_attempt = datetime.now()
            
            logger.info(f"Creating new SAP connection...")
            logger.info(f"  Host: {SAP_ASHOST}")
            logger.info(f"  System Number: {SAP_SYSNR}")
            logger.info(f"  Client: {SAP_CLIENT}")
            logger.info(f"  User: {SAP_USER}")
            logger.info(f"  Password: {'******' if SAP_PASSWD else 'NOT SET'}")
            
            # First test network connectivity
            if not self._test_sap_connection():
                self.connection_status = 'network_error'
                self.connection_error = f"Cannot reach SAP server {SAP_ASHOST}"
                logger.error(f"SAP connection failed: Network unreachable")
                return None
            
            logger.info(f"Establishing RFC connection to SAP...")
            
            # Create new connection
            conn = Connection(
                ashost=SAP_ASHOST,
                sysnr=SAP_SYSNR,
                client=SAP_CLIENT,
                user=SAP_USER,
                passwd=SAP_PASSWD,
                lang=SAP_LANG
            )
            
            # Test connection with a simple RFC call
            try:
                logger.info("Testing RFC connection with RFC_PING...")
                ping_result = conn.call('RFC_PING')
                logger.info(f"SAP RFC_PING successful: {ping_result}")
                
                self.connection_status = 'connected'
                self.connection_error = None
                logger.info("✓ SAP connection created successfully")
                return conn
                
            except RFCError as ping_error:
                logger.error(f"SAP RFC_PING failed: {ping_error}")
                self.connection_status = 'rfc_error'
                self.connection_error = f"RFC error: {ping_error}"
                try:
                    conn.close()
                except:
                    pass
                return None
                
        except RFCError as e:
            logger.error(f"SAP Connection RFC Error: {str(e)}")
            self.connection_status = 'rfc_error'
            self.connection_error = f"RFC Error: {str(e)}"
            return None
            
        except Exception as e:
            logger.error(f"SAP Connection Error: {str(e)}")
            logger.exception(e)  # Log full traceback
            self.connection_status = 'error'
            self.connection_error = f"Connection error: {str(e)}"
            return None
    
    def _close_connection(self, conn):
        """Close SAP connection if it exists"""
        if conn:
            try:
                conn.close()
                logger.debug("SAP connection closed")
            except Exception as e:
                logger.warning(f"Error closing SAP connection: {e}")
    
    def format_material_code_for_sap(self, material_code: str) -> str:
        """
        Format material code for SAP query
        - If material code is numeric only: pad to 18 digits with leading zeros
        - If material code contains letters: use as-is without leading zeros
        """
        code_str = str(material_code).strip()
        
        # Check if the code contains only digits
        if code_str.isdigit():
            # Remove existing leading zeros to avoid double padding
            clean_code = code_str.lstrip('0')
            if not clean_code:  # If all zeros, keep one zero
                clean_code = '0'
            # Pad to 18 digits with leading zeros
            return clean_code.zfill(18)
        else:
            # For alphanumeric codes, use as-is
            return code_str
    
    def get_material_by_code(self, material_code: str) -> Optional[Dict[str, Any]]:
        """
        Get material details using RFC Z_RFC_GET_MATERIAL_BY_DESC2
        Extract data from ET_MAT_DETAIL table (structure ZBAPIMATDOA)
        
        NOTE: Opens and closes connection for each call
        """
        conn = None
        try:
            if not PYRFC_AVAILABLE:
                logger.error("pyrfc not available, cannot query SAP")
                return None
            
            # Create new connection for this request
            conn = self._create_connection()
            if not conn:
                logger.error("SAP connection not available")
                return None
            
            # Format material code for SAP
            formatted_code = self.format_material_code_for_sap(material_code)
            logger.info(f"Querying SAP for material: '{material_code}' (formatted: '{formatted_code}')")
            
            # Call SAP RFC
            result = conn.call(
                'Z_RFC_GET_MATERIAL_BY_DESC2',
                IV_MATNR=formatted_code,
                IV_MAKTX=''  # Empty string for description (not used)
            )
            
            # Get materials from ET_MAT_DETAIL table
            materials = result.get('ET_MAT_DETAIL', [])
            
            if not materials:
                logger.warning(f"No material found for code: {material_code} (formatted as: {formatted_code})")
                return None
            
            # Get first material from the list
            material_data = materials[0]
            
            # Konversi ST ke PC jika diperlukan
            base_unit = material_data.get('BASE_UOM', '').strip()
            if base_unit == 'ST':
                base_unit = 'PC'
            
            material_info = {
                # Field yang diharapkan frontend
                'material_code': str(material_code).strip(),  # Gunakan input user asli
                'description': material_data.get('MATL_DESC', '').strip(),
                'material_type': material_data.get('MATL_TYPE_DESC', '').strip(),
                'material_group': material_data.get('MATL_GROUP_DESC', '').strip(),
                'base_unit': base_unit,
                'division': material_data.get('DIVISION', '').strip(),
                'gross_weight': float(material_data.get('GROSS_WT', 0)) if material_data.get('GROSS_WT') else 0,
                'weight_unit': material_data.get('UNIT_OF_WT', '').strip(),
                'size_dimension': material_data.get('SIZE_DIM', '').strip(),
                
                # Field tambahan untuk debug
                'sap_material_code': material_data.get('MATERIAL', '').strip(),
                'old_material_no': material_data.get('OLD_MAT_NO', '').strip(),
                'industry_sector': material_data.get('IND_SECTOR', '').strip(),
                'created_on': material_data.get('CREATED_ON', '').strip(),
                'created_by': material_data.get('CREATED_BY', '').strip(),
                'last_change': material_data.get('LAST_CHNGE', '').strip(),
                'changed_by': material_data.get('CHANGED_BY', '').strip(),
                'net_weight': float(material_data.get('NET_WEIGHT', 0)) if material_data.get('NET_WEIGHT') else 0,
                'volume': float(material_data.get('VOLUME', 0)) if material_data.get('VOLUME') else 0,
                'volume_unit': material_data.get('VOLUMEUNIT', '').strip(),
                'length': float(material_data.get('LENGTH', 0)) if material_data.get('LENGTH') else 0,
                'width': float(material_data.get('WIDTH', 0)) if material_data.get('WIDTH') else 0,
                'height': float(material_data.get('HEIGHT', 0)) if material_data.get('HEIGHT') else 0,
                'unit_dim': material_data.get('UNIT_DIM', '').strip(),
                'ean_upc': material_data.get('EAN_UPC', '').strip(),
                'is_valid': True
            }
            
            logger.info(f"✓ Material found: {material_info['material_code']} (SAP: {material_info['sap_material_code']})")
            logger.info(f"  Description: {material_info['description']}")
            logger.info(f"  Material Type: {material_info['material_type']}")
            logger.info(f"  Material Group: {material_info['material_group']}")
            logger.info(f"  Base Unit: {material_info['base_unit']}")
            
            return material_info
            
        except RFCError as e:
            logger.error(f"SAP RFC Error for material {material_code}: {e}")
            return None
        except Exception as e:
            logger.error(f"Error getting material {material_code}: {e}")
            return None
        finally:
            # Always close connection
            self._close_connection(conn)
    
    def validate_material(self, material_code: str, plant: str = "") -> Dict[str, Any]:
        """
        Validate if material exists in SAP system (Material Code Only)
        
        NOTE: Opens and closes connection for each call
        """
        try:
            if not PYRFC_AVAILABLE:
                return {
                    'status': 'error',
                    'is_valid': False,
                    'message':'SAP RFC module not available. Please install pyrfc.',
                    'module_error': True
                }
            
            material_info = self.get_material_by_code(material_code)
            
            if not material_info:
                return {
                    'status': 'error',
                    'is_valid': False,
                    'message' : f'Material {material_code} not found in SAP',
                    'search_tips': [
                        'Check if material code is correct',
                        'For numeric codes, SAP stores them as 18-digit with leading zeros',
                        'For alphanumeric codes, search with exact characters'
                    ]
                }
            
            # If numeric code, show both formats
            if material_code.isdigit():
                formatted_code = self.format_material_code_for_sap(material_code)
                material_info['formatted_sap_code'] = formatted_code
                material_info['original_input'] = material_code
            
            return {
                'status': 'success',
                'is_valid': True,
                'message' : 'Material validated successfully',
                'material': material_info,
                'plant': plant if plant else 'Not specified'
            }
            
        except Exception as e:
            logger.error(f"Material validation error: {e}")
            return {
                'status' : 'error',
                'is_valid' : False,
                'message' : f'Validation error: {str(e)}'
            }
    
    def search_material_by_code_only(self, material_code: str) -> List[Dict[str, Any]]:
        """
        Search materials by material code only (exact match)
        
        NOTE: Opens and closes connection for each call
        """
        try:
            if not PYRFC_AVAILABLE:
                return []
            
            material_info = self.get_material_by_code(material_code)
            
            if not material_info:
                return []
            
            return [material_info]
            
        except Exception as e:
            logger.error(f"Search by code error: {e}")
            return []

# ==================== DROPBOX MANAGER (DUAL APPROACH) ====================

class DropboxManager:
    """Manages Dropbox connection with OAuth 2.0 and automatic token refresh"""
    
    def __init__(self, token_manager: TokenManager):
        self.token_manager = token_manager
        self.dbx = None
        self._initialize_dropbox()
    
    def _initialize_dropbox(self):
        """Initialize Dropbox connection"""
        try:
            logger.info("Initializing Dropbox connection...")
            
            # Check if we need OAuth first
            if self.token_manager.needs_oauth():
                logger.warning("No valid Dropbox tokens found. OAuth required.")
                return False
            
            # Get current access token
            access_token = self.token_manager.get_access_token()
            if not access_token:
                logger.error("No Dropbox access token available!")
                return False
            
            logger.info(f"Using access token (first 10 chars): {access_token[:10]}...")
            
            # Initialize Dropbox with access token
            self.dbx = dropbox.Dropbox(
                oauth2_access_token=access_token,
                timeout=30,
                max_retries_on_error=3
            )
            
            # Test connection
            try:
                account_info = self.dbx.users_get_current_account()
                logger.info("Dropbox connected successfully!")
                logger.info(f"  Account: {account_info.name.display_name}")
                logger.info(f"  Email: {account_info.email}")
                return True
                
            except AuthError as e:
                logger.error(f"AuthError during connection test: {e}")
                
                if "expired" in str(e).lower() or "invalid" in str(e).lower():
                    logger.warning(f"Access token invalid/expired: {e}")
                    # Try to refresh
                    if self.token_manager.refresh_access_token():
                        # Get new token
                        new_token = self.token_manager.get_access_token()
                        self.dbx = dropbox.Dropbox(
                            oauth2_access_token=new_token,
                            timeout=30,
                            max_retries_on_error=3
                        )
                        
                        # Test again
                        try:
                            account_info = self.dbx.users_get_current_account()
                            logger.info("Dropbox reconnected successfully after refresh!")
                            return True
                        except Exception as e2:
                            logger.error(f"Still cannot connect after refresh: {e2}")
                            return False
                    else:
                        logger.error("Failed to refresh token. Need OAuth.")
                        return False
                elif "missing_scope" in str(e):
                    logger.error(f"Missing scope error: {e}")
                    logger.error("Current token doesn't have required scopes. Need to re-authenticate via OAuth.")
                    return False
                else:
                    logger.error(f"Dropbox authentication error: {e}")
                    return False
                
        except Exception as e:
            logger.error(f"Failed to initialize Dropbox: {e}")
            return False
    
    def _ensure_valid_connection(self):
        """Ensure Dropbox connection is valid, refresh token if needed"""
        try:
            # Simple test to check if connection is still valid
            self.dbx.users_get_current_account()
            return True
        except AuthError as e:
            if "expired" in str(e).lower() or "invalid" in str(e).lower():
                logger.warning("Dropbox token expired, refreshing...")
                if self.token_manager.refresh_access_token():
                    # Reinitialize with new token
                    new_token = self.token_manager.get_access_token()
                    self.dbx = dropbox.Dropbox(
                        oauth2_access_token=new_token,
                        timeout=30,
                        max_retries_on_error=3
                    )
                    logger.info("Dropbox connection refreshed successfully")
                    return True
                else:
                    logger.error("Failed to refresh token. Need OAuth.")
                    return False
            elif "missing_scope" in str(e):
                logger.error(f"Missing scope error: {e}")
                logger.error("Token doesn't have required scope 'account_info.read'. Need to re-authenticate via OAuth.")
                return False
            else:
                logger.error(f"Auth error: {e}")
                return False
        except Exception as e:
            logger.error(f"Connection check error: {e}")
            return False
    
    def check_connection(self) -> bool:
        """Check if Dropbox connection is valid"""
        if not self.dbx:
            return False
        
        return self._ensure_valid_connection()
    
    def upload_shop_drawing(self, file_content: bytes, filename: str, 
                       material_code: str, plant: str, description: str, 
                       drawing_type: str = "assembly", revision: str = "Rev0",
                       username: str = "N/A", user_id: int = 0) -> Dict[str, Any]:
        """Upload shop drawing to Dropbox with organized folder structure"""
        try:
            # Ensure connection is valid
            if not self._ensure_valid_connection():
                logger.error("Dropbox connection not valid")
                return {'status': 'error', 'message': 'Dropbox connection not valid. Please authenticate via OAuth.'}
            
            # Get file extension from original filename
            file_extension = os.path.splitext(filename)[1].lower()
            
            # Create folder path: /Shop Drawing/{material_code}/
            folder_path = f"/Shop Drawing/{material_code}"
            
            logger.info(f"Creating folder: {folder_path}")
            
            try:
                self.dbx.files_create_folder_v2(folder_path)
                logger.info(f"Folder created successfully")
            except ApiError as e:
                if not e.error.is_path() or not e.error.get_path().is_conflict():
                    logger.error(f"Failed to create folder: {e}")
                    return {'status': 'error', 'message': f'Failed to create folder: {e}'}
                else:
                    logger.info(f"Folder already exists")
            
            # Generate new filename: {material_code}_{revision}_{timestamp}{extension}
            # Clean revision string (remove spaces, special characters)
            clean_revision = re.sub(r'[^\w\-]', '_', revision)
            timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
            # Generate unique identifier to avoid collisions
            unique_id = secrets.token_hex(4)
            new_filename = f"{material_code}_{clean_revision}_{timestamp}_{unique_id}{file_extension}"
            dropbox_path = f"{folder_path}/{new_filename}"
            
            logger.info(f"Uploading file to: {dropbox_path}")
            logger.info(f"Original filename: {filename}")
            logger.info(f"New filename: {new_filename}")
            logger.info(f"Drawing Type: {drawing_type}")
            logger.info(f"Revision: {revision}")
            logger.info(f"User ID from Laravel: {user_id}")
            logger.info(f"Username: {username}")
            
            # Check if file already exists
            try:
                existing_file = self.dbx.files_get_metadata(dropbox_path)
                if existing_file:
                    logger.info(f"File already exists at {dropbox_path}, will overwrite")
            except ApiError as e:
                if not e.error.is_path() or not e.error.get_path().is_not_found():
                    logger.warning(f"Error checking existing file: {e}")
            
            # Upload file (max 150MB)
            if len(file_content) > 150 * 1024 * 1024:
                return {'status': 'error', 'message': f'File size {len(file_content)/1024/1024:.2f}MB exceeds 150MB limit'}
            
            self.dbx.files_upload(
                file_content,
                dropbox_path,
                mode=WriteMode('overwrite'),
                client_modified=datetime.now(),
                mute=True
            )
            
            logger.info(f"File uploaded successfully")
            
            # Create shared link
            try:
                shared_link = self.dbx.sharing_create_shared_link_with_settings(
                    dropbox_path,
                    settings=dropbox.sharing.SharedLinkSettings(
                        requested_visibility=dropbox.sharing.RequestedVisibility.public,
                        audience=dropbox.sharing.LinkAudience.public,
                        access=dropbox.sharing.RequestedLinkAccessLevel.viewer
                    )
                )
                share_url = shared_link.url
                logger.info(f"Shared link created")
            except ApiError as e:
                if e.error.is_shared_link_already_exists():
                    links = self.dbx.sharing_list_shared_links(path=dropbox_path).links
                    share_url = links[0].url if links else ''
                    logger.info(f"Shared link already exists, reusing it")
                else:
                    logger.warning(f"Could not create shared link: {e}")
                    share_url = ''
            
            # Create direct download URL
            direct_url = share_url.replace('www.dropbox.com', 'dl.dropboxusercontent.com').replace('?dl=0', '') if share_url else ''
            
            # Get file metadata
            file_meta = self.dbx.files_get_metadata(dropbox_path)
            
            # Dapatkan informasi material dari SAP atau database
            material_info = None
            material_type = 'N/A'
            material_group = 'N/A'
            base_unit = 'N/A'
            
            try:
                sap_validator = get_sap_validator()
                material_info = sap_validator.get_material_by_code(material_code)
                if material_info:
                    material_type = material_info.get('material_type', 'N/A')
                    material_group = material_info.get('material_group', 'N/A')
                    base_unit = material_info.get('base_unit', 'N/A')
                    # Konversi ST ke PC jika diperlukan
                    if base_unit == 'ST':
                        base_unit = 'PC'
                else:
                    # Coba ambil dari database jika ada
                    db_material_info = DatabaseHelper.get_material_info_from_db(material_code)
                    if db_material_info:
                        material_type = db_material_info.get('material_type', 'N/A')
                        material_group = db_material_info.get('material_group', 'N/A')
                        base_unit = db_material_info.get('base_unit', 'N/A')
            except Exception as sap_error:
                logger.warning(f"SAP validation skipped due to error: {sap_error}")
            
            result = {
                'status': 'success',
                'file_id': file_meta.id,
                'path': dropbox_path,
                'share_url': share_url,
                'direct_url': direct_url,
                'filename': new_filename,
                'original_filename': filename,
                'size': file_meta.size,
                'modified': file_meta.server_modified.isoformat(),
                'material_code': material_code,
                'plant': plant,
                'description': description,
                'drawing_type': drawing_type,
                'revision': revision,
                'material_type': material_type,
                'material_group': material_group,
                'base_unit': base_unit,
                'material_info': material_info,
                'user_id': user_id,
                'username': username
            }
            
            logger.info(f"Upload completed successfully for material: {material_code}")
            logger.info(f"Material Type: {material_type}")
            logger.info(f"Material Group: {material_group}")
            logger.info(f"Base Unit: {base_unit}")
            
            # PERBAIKAN: HAPUS penyimpanan ke database dari Python service
            # Database hanya disimpan dari Laravel untuk menghindari duplikasi
            logger.info("✓ File uploaded to Dropbox successfully. Database record will be saved by Laravel.")
            
            return result
            
        except ApiError as e:
            logger.error(f"Dropbox API error: {e}")
            
            error_msg = str(e)
            if 'files.content.write' in error_msg:
                error_msg += "\n\nSOLUTION:\n1. Go to https://www.dropbox.com/developers/apps\n2. Select your app\n3. Go to 'Permissions' tab\n4. Enable 'files.content.write' permission\n5. Generate new access token\n6. Update .env file"
            
            return {'status': 'error', 'message': f'Dropbox API error: {error_msg}'}
        except Exception as e:
            logger.error(f"Upload failed: {e}")
            return {'status': 'error', 'message': str(e)}
    
    def delete_shop_drawing(self, file_id: str, path: str) -> Dict[str, Any]:
        """Delete shop drawing from Dropbox"""
        try:
            # Ensure connection is valid
            if not self._ensure_valid_connection():
                return {'status': 'error', 'message': 'Dropbox connection not valid'}
            
            self.dbx.files_delete_v2(path)
            logger.info(f"Deleted file: {path}")
            return {'status': 'success', 'message': 'File deleted successfully'}
        except Exception as e:
            logger.error(f"Delete failed: {e}")
            return {'status': 'error', 'message': str(e)}
    
    # ==================== OPSI 1: SEARCH RECURSIVE DI DROPBOX ====================
    
    def list_shop_drawings_recursive(self, material_code: str, plant: str = None) -> Dict[str, Any]:
        """OPTION 1: Search recursively in all Shop Drawing folders for material code"""
        try:
            # Ensure connection is valid
            if not self._ensure_valid_connection():
                return {'status': 'error', 'message': 'Dropbox connection not valid. Please authenticate via OAuth.'}
            
            results = []
            base_path = "/Shop Drawing"
            
            logger.info(f"Starting recursive search for material: {material_code}")
            
            try:
                # Search recursively
                result = self.dbx.files_list_folder(base_path, recursive=True)
                all_entries = result.entries
                
                # Get all pages if more results
                while result.has_more:
                    result = self.dbx.files_list_folder_continue(result.cursor)
                    all_entries.extend(result.entries)
                
                logger.info(f"Total entries found in Dropbox: {len(all_entries)}")
                
                # Filter for material code in path
                for entry in all_entries:
                    if isinstance(entry, FileMetadata):
                        # Check if material code is in path and file is image/drawing
                        if (f"/{material_code}/" in entry.path_display and 
                            entry.path_lower.endswith(('.jpg', '.jpeg', '.png', '.gif', '.bmp', '.pdf', '.dwg', '.dxf'))):
                            
                            try:
                                links = self.dbx.sharing_list_shared_links(path=entry.path_lower).links
                                share_url = links[0].url if links else None
                                direct_url = share_url.replace('www.dropbox.com', 'dl.dropboxusercontent.com').replace('?dl=0', '') if share_url else None
                            except:
                                share_url = None
                                direct_url = None
                            
                            # Extract drawing type from filename pattern or default
                            filename = entry.name
                            # Pattern: {material_code}_{revision}_{timestamp}_{unique_id}.{extension}
                            parts = filename.split('_')
                            if len(parts) >= 2:
                                file_material_code = parts[0]
                                file_revision = parts[1] if len(parts) > 1 else 'Rev0'
                            else:
                                file_material_code = material_code
                                file_revision = 'Rev0'
                            
                            # Try to extract drawing type from filename or use default
                            # Check if filename contains drawing type keywords
                            file_drawing_type = 'assembly'  # default
                            filename_lower = filename.lower()
                            if 'detail' in filename_lower:
                                file_drawing_type = 'detail'
                            elif 'exploded' in filename_lower:
                                file_drawing_type = 'exploded'
                            elif 'orthographic' in filename_lower or '2d' in filename_lower:
                                file_drawing_type = 'orthographic'
                            elif 'perspective' in filename_lower or '3d' in filename_lower:
                                file_drawing_type = 'perspective'
                            
                            # Filter by material code
                            if file_material_code != material_code:
                                continue
                            
                            results.append({
                                'source': 'dropbox_recursive',
                                'filename': filename,
                                'path': entry.path_display,
                                'size': entry.size,
                                'modified': entry.server_modified.isoformat(),
                                'share_url': share_url,
                                'direct_url': direct_url,
                                'plant': plant if plant else 'Not specified',
                                'material_code': material_code,
                                'drawing_type': file_drawing_type,
                                'revision': file_revision
                            })
                
                logger.info(f"Found {len(results)} drawings via recursive search")
                
            except ApiError as e:
                if e.error.is_path() and e.error.get_path().is_not_found():
                    logger.info(f"No Shop Drawing folder found at: {base_path}")
                else:
                    logger.error(f"Dropbox API error: {e}")
                    return {'status': 'error', 'message': f'Dropbox API error: {e}'}
            
            return {
                'status': 'success', 
                'drawings': results,
                'search_method': 'recursive',
                'count': len(results)
            }
            
        except Exception as e:
            logger.error(f"Recursive search failed: {e}")
            return {'status': 'error', 'message': str(e)}
    
    # ==================== OPSI 2: LIST STANDARD DENGAN PATH TERTENTU ====================
    
    def list_shop_drawings(self, material_code: str, plant: str = None) -> Dict[str, Any]:
        """OPTION 2: List drawings using specific path structure (material_code folder only)"""
        try:
            # Ensure connection is valid
            if not self._ensure_valid_connection():
                return {'status': 'error', 'message': 'Dropbox connection not valid. Please authenticate via OAuth.'}
            
            # Search in material folder
            search_path = f"/Shop Drawing/{material_code}"
            results = []
            
            try:
                result = self.dbx.files_list_folder(search_path, recursive=False)
                
                for entry in result.entries:
                    if isinstance(entry, FileMetadata):
                        if entry.path_lower.endswith(('.jpg', '.jpeg', '.png', '.gif', '.bmp', '.pdf', '.dwg', '.dxf')):
                            try:
                                links = self.dbx.sharing_list_shared_links(path=entry.path_lower).links
                                share_url = links[0].url if links else None
                                direct_url = share_url.replace('www.dropbox.com', 'dl.dropboxusercontent.com').replace('?dl=0', '') if share_url else None
                            except:
                                share_url = None
                                direct_url = None
                            
                            # Extract revision and drawing type from filename
                            filename = entry.name
                            # Pattern: {material_code}_{revision}_{timestamp}_{unique_id}.{extension}
                            parts = filename.split('_')
                            if len(parts) >= 2:
                                file_revision = parts[1] if len(parts) > 1 else 'Rev0'
                            else:
                                file_revision = 'Rev0'
                            
                            # Try to extract drawing type from filename or use default
                            file_drawing_type = 'assembly'  # default
                            filename_lower = filename.lower()
                            if 'detail' in filename_lower:
                                file_drawing_type = 'detail'
                            elif 'exploded' in filename_lower:
                                file_drawing_type = 'exploded'
                            elif 'orthographic' in filename_lower or '2d' in filename_lower:
                                file_drawing_type = 'orthographic'
                            elif 'perspective' in filename_lower or '3d' in filename_lower:
                                file_drawing_type = 'perspective'
                            
                            results.append({
                                'source': 'dropbox_standard',
                                'filename': filename,
                                'path': entry.path_display,
                                'size': entry.size,
                                'modified': entry.server_modified.isoformat(),
                                'share_url': share_url,
                                'direct_url': direct_url,
                                'plant': plant if plant else 'Not specified',
                                'material_code': material_code,
                                'drawing_type': file_drawing_type,
                                'revision': file_revision
                            })
            except ApiError as e:
                if e.error.is_path() and e.error.get_path().is_not_found():
                    logger.info(f"No drawings found at: {search_path}")
                else:
                    raise
            
            return {
                'status': 'success', 
                'drawings': results,
                'search_method': 'standard',
                'count': len(results)
            }
        except Exception as e:
            logger.error(f"List drawings failed: {e}")
            return {'status': 'error', 'message': str(e)}

# ==================== SERVICE INITIALIZATION ====================

# Initialize services
sap_validator = None
token_manager = None
dropbox_manager = None

def get_token_manager():
    global token_manager
    if token_manager is None:
        token_manager = TokenManager()
    return token_manager

def get_sap_validator():
    global sap_validator
    if sap_validator is None:
        sap_validator = SAPMaterialValidator()
    return sap_validator

def get_dropbox_manager():
    global dropbox_manager
    if dropbox_manager is None:
        token_mgr = get_token_manager()
        dropbox_manager = DropboxManager(token_mgr)
    return dropbox_manager

# Authentication decorator
def require_auth(f):
    @wraps(f)
    def decorated_function(*args, **kwargs):
        auth_header = request.headers.get('Authorization')
        if not auth_header:
            return jsonify({'status': 'error', 'message': 'Authorization required'}), 401
        
        if auth_header != f"Bearer {API_TOKEN}":
            return jsonify({'status': 'error', 'message': 'Invalid token'}), 401
        
        return f(*args, **kwargs)
    return decorated_function

# ==================== SESSION MANAGEMENT MIDDLEWARE ====================

@app.before_request
def before_request():
    """Ensure session is initialized before each request"""
    session.permanent = True
    app.permanent_session_lifetime = timedelta(hours=1)
    
    # Initialize session if not exists
    if 'initialized' not in session:
        session['initialized'] = True
        session['created_at'] = datetime.now().isoformat()

# ==================== API ENDPOINTS ====================

@app.route('/', methods=['GET'])
def home():
    """Home page with OAuth instructions"""
    return '''
    <!DOCTYPE html>
    <html>
    <head>
        <title>Dropbox Service - Shop Drawing Management</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
            .container { max-width: 800px; margin: 0 auto; }
            .button { 
                display: inline-block; 
                padding: 12px 24px; 
                background-color: #0061ff; 
                color: white; 
                text-decoration: none; 
                border-radius: 4px; 
                font-weight: bold;
                margin: 10px 5px;
            }
            .button:hover { background-color: #0051d6; }
            .card { 
                background: #f5f5f5; 
                padding: 20px; 
                border-radius: 8px; 
                margin: 20px 0;
                border-left: 4px solid #0061ff;
            }
            .error { color: #d32f2f; }
            .success { color: #388e3c; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Dropbox Service - Shop Drawing Management</h1>
            
            <div class="card">
                <h3>Status Dropbox Connection</h3>
                <div id="status">Checking...</div>
                <button onclick="checkStatus()" class="button">Check Status</button>
            </div>
            
            <div class="card">
                <h3>Authentication</h3>
                <p>You need to authenticate with Dropbox to use this service.</p>
                <a href="/oauth/start" class="button">Start Dropbox OAuth</a>
                <a href="/test_dropbox_connection" class="button">Test Connection</a>
                <a href="/health" class="button">Health Check</a>
            </div>
            
            <div class="card">
                <h3>Service Endpoints</h3>
                <ul>
                    <li><strong>POST /upload_shop_drawing</strong> - Upload shop drawing</li>
                    <li><strong>GET /list_shop_drawings?material_code=XXX</strong> - List drawings (multi-method)</li>
                    <li><strong>GET /list_shop_drawings_from_db?material_code=XXX</strong> - List from database</li>
                    <li><strong>GET /list_shop_drawings_recursive?material_code=XXX</strong> - Recursive search</li>
                    <li><strong>POST /validate_material</strong> - Validate SAP material</li>
                    <li><strong>POST /search_material</strong> - Search material</li>
                </ul>
            </div>
        </div>
        
        <script>
            function checkStatus() {
                fetch('/health')
                    .then(response => response.json())
                    .then(data => {
                        const statusDiv = document.getElementById('status');
                        const dropboxStatus = data.services?.dropbox || 'unknown';
                        statusDiv.innerHTML = `
                            <p><strong>Overall:</strong> ${data.status}</p>
                            <p><strong>Dropbox:</strong> <span class="${dropboxStatus === 'connected' ? 'success' : 'error'}">${dropboxStatus}</span></p>
                            <p><strong>SAP:</strong> ${data.services?.sap || 'unknown'}</p>
                            <p><strong>Timestamp:</strong> ${data.timestamp}</p>
                        `;
                    })
                    .catch(error => {
                        document.getElementById('status').innerHTML = '<p class="error">Error checking status</p>';
                    });
            }
            
            // Check status on page load
            checkStatus();
        </script>
    </body>
    </html>
    '''

@app.route('/health', methods=['GET'])
def health_check():
    try:
        health_info = {
            'status': 'healthy',
            'services': {},
            'timestamp': datetime.now().isoformat(),
            'details': {}
        }
        
        # Check SAP connection
        try:
            sap_validator = get_sap_validator()
            if not PYRFC_AVAILABLE:
                health_info['services']['sap'] = 'module_not_available'
                health_info['details']['sap'] = 'pyrfc module not installed'
            else:
                # Test SAP connection by creating and closing a connection
                conn = sap_validator._create_connection()
                if conn:
                    sap_validator._close_connection(conn)
                    health_info['services']['sap'] = 'connected'
                    health_info['details']['sap'] = {
                        'status': sap_validator.connection_status,
                        'last_attempt': sap_validator.last_connection_attempt.isoformat() if sap_validator.last_connection_attempt else None
                    }
                else:
                    health_info['services']['sap'] = 'disconnected'
                    health_info['details']['sap'] = {
                        'status': sap_validator.connection_status,
                        'error': sap_validator.connection_error,
                        'last_attempt': sap_validator.last_connection_attempt.isoformat() if sap_validator.last_connection_attempt else None
                    }
        except Exception as e:
            logger.error(f"SAP health check error: {e}")
            health_info['services']['sap'] = f'error: {str(e)}'
        
        # Check Dropbox connection
        try:
            dropbox_mgr = get_dropbox_manager()
            if dropbox_mgr.check_connection():
                health_info['services']['dropbox'] = 'connected'
            else:
                health_info['services']['dropbox'] = 'disconnected'
        except Exception as e:
            logger.error(f"Dropbox health check error: {e}")
            health_info['services']['dropbox'] = f'error: {str(e)}'
        
        # Check database connection
        try:
            if not PYMYSQL_AVAILABLE:
                health_info['services']['database'] = 'module_not_available'
            else:
                connection = DatabaseHelper.get_connection()
                if connection:
                    connection.close()
                    health_info['services']['database'] = 'connected'
                else:
                    health_info['services']['database'] = 'disconnected'
        except Exception as e:
            logger.error(f"Database health check error: {e}")
            health_info['services']['database'] = f'error: {str(e)}'
        
        # Jika ada service yang disconnected atau error, ubah status overall
        if any(status in ['disconnected', 'module_not_available'] for status in health_info['services'].values()):
            health_info['status'] = 'degraded'
        elif any('error' in str(status).lower() for status in health_info['services'].values()):
            health_info['status'] = 'unhealthy'
        
        return jsonify(health_info)
    except Exception as e:
        logger.error(f"Health check failed: {e}")
        return jsonify({
            'status': 'unhealthy',
            'error': str(e),
            'timestamp': datetime.now().isoformat()
        }), 500

@app.route('/oauth/start', methods=['GET'])
def oauth_start():
    """Start OAuth flow - opens browser automatically"""
    try:
        if not DROPBOX_APP_KEY or not DROPBOX_APP_SECRET:
            return jsonify({'status': 'error', 'message': 'Dropbox app credentials not configured'}), 400
        
        # Generate state for security
        state = secrets.token_urlsafe(16)
        oauth_state[state] = {'time': datetime.now().isoformat()}
        
        # Create OAuth flow - TAMBAHKAN SCOPE 'account_info.read'
        auth_flow = DropboxOAuth2FlowNoRedirect(
            DROPBOX_APP_KEY,
            consumer_secret=DROPBOX_APP_SECRET,
            token_access_type='offline',
            scope=['files.content.write', 'files.metadata.write', 'sharing.write', 'account_info.read']
        )
        
        authorize_url = auth_flow.start()
        
        # Store the auth flow state in session
        session['oauth_state'] = state
        session['oauth_flow'] = {
            'app_key': DROPBOX_APP_KEY,
            'app_secret': DROPBOX_APP_SECRET,
            'authorize_url': authorize_url
        }
        session['oauth_created'] = datetime.now().isoformat()
        
        # Try to open browser automatically
        try:
            webbrowser.open(authorize_url)
            message = f'Browser opened with authorization URL. If browser did not open, please visit this URL manually: {authorize_url}'
        except:
            message = f'Please visit this URL in your browser: {authorize_url}'
        
        return f'''
        <!DOCTYPE html>
        <html>
        <head>
            <title>Dropbox OAuth</title>
            <style>
                body {{ font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }}
                .container {{ max-width: 800px; margin: 0 auto; }}
                .button {{ 
                    display: inline-block; 
                    padding: 12px 24px; 
                    background-color: #0061ff; 
                    color: white; 
                    text-decoration: none; 
                    border-radius: 4px; 
                    font-weight: bold;
                    margin: 10px 5px;
                }}
                .input {{ 
                    width: 100%; 
                    padding: 10px; 
                    margin: 10px 0; 
                    border: 1px solid #ddd; 
                    border-radius: 4px;
                }}
            </style>
        </head>
        <body>
            <div class="container">
                <h2>Dropbox OAuth Authorization</h2>
                <p>{message}</p>
                
                <h3>Manual Authorization</h3>
                <ol>
                    <li>Click the link below to authorize</li>
                    <li>Log in to Dropbox if needed</li>
                    <li>Click "Allow" to grant permissions</li>
                    <li>Copy the authorization code shown on the page</li>
                    <li>Paste it below and submit</li>
                </ol>
                
                <p><a href="{authorize_url}" target="_blank" class="button">Open Authorization URL</a></p>
                
                <form action="/oauth/complete" method="POST">
                    <input type="hidden" name="state" value="{state}">
                    <label for="auth_code">Authorization Code:</label><br>
                    <input type="text" id="auth_code" name="auth_code" class="input" placeholder="Paste authorization code here" required><br>
                    <button type="submit" class="button">Complete OAuth</button>
                </form>
            </div>
        </body>
        </html>
        '''
    except Exception as e:
        logger.error(f"OAuth start error: {e}")
        return jsonify({'status': 'error', 'message': str(e)}), 500

@app.route('/oauth/complete', methods=['POST'])
def oauth_complete():
    """Complete OAuth flow with authorization code"""
    try:
        auth_code = request.form.get('auth_code')
        state = request.form.get('state')
        
        if not auth_code:
            return jsonify({'status': 'error', 'message': 'Missing authorization code'}), 400
        
        if not DROPBOX_APP_KEY or not DROPBOX_APP_SECRET:
            return jsonify({'status': 'error', 'message': 'Dropbox app credentials not configured'}), 400
        
        logger.info(f"Completing OAuth with code: {auth_code[:10]}...")
        
        try:
            # Create new auth flow
            auth_flow = DropboxOAuth2FlowNoRedirect(
                DROPBOX_APP_KEY,
                consumer_secret=DROPBOX_APP_SECRET,
                token_access_type='offline'
            )
            
            # Exchange authorization code for tokens
            result = auth_flow.finish(auth_code.strip())
            
            refresh_token = result.refresh_token
            access_token = result.access_token
            
            logger.info(f"OAuth successful! Refresh token: {refresh_token[:10]}...")
            
            # Save tokens
            token_mgr = get_token_manager()
            token_mgr.update_tokens(
                access_token=access_token,
                refresh_token=refresh_token
            )
            
            # Clear session
            session.pop('oauth_state', None)
            session.pop('oauth_flow', None)
            
            return f'''
            <!DOCTYPE html>
            <html>
            <head>
                <title>OAuth Complete</title>
                <style>
                    body {{ font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }}
                    .container {{ max-width: 800px; margin: 0 auto; }}
                    .success {{ 
                        background: #d4edda; 
                        color: #155724; 
                        padding: 20px; 
                        border-radius: 8px; 
                        margin: 20px 0;
                        border: 1px solid #c3e6cb;
                    }}
                    .button {{ 
                        display: inline-block; 
                        padding: 12px 24px; 
                        background-color: #0061ff; 
                        color: white; 
                        text-decoration: none; 
                        border-radius: 4px; 
                        font-weight: bold;
                        margin: 10px 5px;
                    }}
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="success">
                        <h2>✅ OAuth Completed Successfully!</h2>
                        <p>Dropbox tokens have been saved and the service is now ready to use.</p>
                        <p><strong>Access Token:</strong> {access_token[:20]}...</p>
                        <p><strong>Refresh Token:</strong> {refresh_token[:20]}...</p>
                    </div>
                    
                    <h3>Next Steps</h3>
                    <ol>
                        <li><a href="/test_dropbox_connection" class="button">Test Connection</a></li>
                        <li><a href="/" class="button">Go to Home</a></li>
                        <li><a href="/health" class="button">Check Health</a></li>
                    </ol>
                </div>
            </body>
            </html>
            '''
            
        except Exception as oauth_error:
            logger.error(f"OAuth exchange error: {str(oauth_error)}")
            return f'''
            <!DOCTYPE html>
            <html>
            <head>
                <title>OAuth Error</title>
                <style>
                    body {{ font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }}
                    .container {{ max-width: 800px; margin: 0 auto; }}
                    .error {{ 
                        background: #f8d7da; 
                        color: #721c24; 
                        padding: 20px; 
                        border-radius: 8px; 
                        margin: 20px 0;
                        border: 1px solid #f5c6cb;
                    }}
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="error">
                        <h2>❌ OAuth Failed</h2>
                        <p><strong>Error:</strong> {str(oauth_error)}</p>
                        <p>Possible causes:</p>
                        <ul>
                            <li>Authorization code was already used</li>
                            <li>Authorization code expired (codes expire quickly)</li>
                            <li>Wrong authorization code</li>
                        </ul>
                        <p><a href="/oauth/start">Try again</a></p>
                    </div>
                </div>
            </body>
            </html>
            '''
        
    except Exception as e:
        logger.error(f"OAuth completion error: {e}")
        return jsonify({'status': 'error', 'message': str(e)}), 500

# ==================== DUAL APPROACH ENDPOINTS ====================

@app.route('/list_shop_drawings', methods=['GET'])
@require_auth
def list_shop_drawings():
    """
    MULTI-METHOD: List shop drawings with dual approach
    Option 1: Try database first
    Option 2: If no results, try Dropbox recursive search
    """
    try:
        material_code = request.args.get('material_code')
        plant = request.args.get('plant')
        force_method = request.args.get('method', 'auto')  # auto, db, dropbox
        
        if not material_code:
            return jsonify({'status': 'error', 'message': 'Missing material_code'}), 400
        
        logger.info(f"Multi-method search for material: {material_code}, Plant: {plant}, Method: {force_method}")
        
        all_results = []
        methods_used = []
        
        # OPTION 1: DATABASE FIRST (if auto or db)
        if force_method in ['auto', 'db']:
            logger.info("Trying Option 1: Database search...")
            db_results = DatabaseHelper.get_shop_drawings_from_db(material_code, plant)
            methods_used.append('database')
            
            if db_results:
                all_results.extend(db_results)
                logger.info(f"Found {len(db_results)} drawings in database")
        
        # OPTION 2: DROPBOX RECURSIVE SEARCH (if auto or no db results or force dropbox)
        if force_method in ['auto', 'dropbox'] or (force_method == 'auto' and not all_results):
            logger.info("Trying Option 2: Dropbox recursive search...")
            dropbox_mgr = get_dropbox_manager()
            dropbox_result = dropbox_mgr.list_shop_drawings_recursive(material_code, plant)
            methods_used.append('dropbox_recursive')
            
            if dropbox_result['status'] == 'success' and dropbox_result['drawings']:
                # Add only unique drawings (by path)
                existing_paths = {d['path'] for d in all_results if 'path' in d}
                for drawing in dropbox_result['drawings']:
                    if drawing['path'] not in existing_paths:
                        all_results.append(drawing)
                logger.info(f"Found {len(dropbox_result['drawings'])} drawings via Dropbox recursive search")
        
        # Remove duplicates by direct_url
        unique_results = []
        seen_urls = set()
        
        for drawing in all_results:
            url = drawing.get('direct_url') or drawing.get('path')
            if url and url not in seen_urls:
                seen_urls.add(url)
                unique_results.append(drawing)
        
        logger.info(f"Total unique drawings found: {len(unique_results)}")
        
        return jsonify({
            'status': 'success',
            'drawings': unique_results,
            'count': len(unique_results),
            'methods_used': methods_used,
            'material_code': material_code,
            'plant': plant if plant else 'all',
            'message': f'Found {len(unique_results)} drawings using {", ".join(methods_used)}'
        })
        
    except Exception as e:
        logger.error(f"Multi-method list error: {e}")
        return jsonify({'status': 'error', 'message': str(e)}), 500

@app.route('/list_shop_drawings_from_db', methods=['GET'])
@require_auth
def list_shop_drawings_from_db():
    """OPTION 1 ONLY: List shop drawings from database"""
    try:
        material_code = request.args.get('material_code')
        plant = request.args.get('plant')
        
        if not material_code:
            return jsonify({'status': 'error', 'message': 'Missing material_code'}), 400
        
        logger.info(f"Database search for material: {material_code}, Plant: {plant}")
        
        drawings = DatabaseHelper.get_shop_drawings_from_db(material_code, plant)
        
        return jsonify({
            'status': 'success',
            'drawings': drawings,
            'count': len(drawings),
            'source': 'database',
            'material_code': material_code,
            'message': f'Found {len(drawings)} drawings in database'
        })
        
    except Exception as e:
        logger.error(f"Database list error: {e}")
        return jsonify({'status': 'error', 'message': str(e)}), 500

@app.route('/list_shop_drawings_recursive', methods=['GET'])
@require_auth
def list_shop_drawings_recursive():
    """OPTION 2 ONLY: Recursive search in Dropbox"""
    try:
        material_code = request.args.get('material_code')
        plant = request.args.get('plant')
        
        if not material_code:
            return jsonify({'status': 'error', 'message': 'Missing material_code'}), 400
        
        logger.info(f"Recursive Dropbox search for material: {material_code}, Plant: {plant}")
        
        dropbox_mgr = get_dropbox_manager()
        result = dropbox_mgr.list_shop_drawings_recursive(material_code, plant)
        
        return jsonify(result)
        
    except Exception as e:
        logger.error(f"Recursive list error: {e}")
        return jsonify({'status': 'error', 'message': str(e)}), 500

# ==================== SAP ENDPOINTS ====================

@app.route('/validate_material', methods=['POST'])
@require_auth
def validate_material():
    """Validate material using SAP RFC (Material Code Only)"""
    try:
        data = request.json
        if not data:
            return jsonify({'status': 'error', 'message': 'No JSON data provided'}), 400
        
        material_code = data.get('material_code')
        plant = data.get('plant', '')
        
        if not material_code:
            return jsonify({'status': 'error', 'message': 'Missing material_code'}), 400
        
        logger.info(f"Validating material: {material_code}, Plant: {plant}")
        
        sap_validator = get_sap_validator()
        validation_result = sap_validator.validate_material(material_code, plant)
        
        if validation_result['status'] == 'error':
            return jsonify(validation_result), 404
        
        return jsonify(validation_result)
        
    except Exception as e:
        logger.error(f"Validation error: {e}")
        return jsonify({'status': 'error', 'message': str(e)}), 500

@app.route('/search_material', methods=['POST'])
@require_auth
def search_material():
    """Search material by code only (no description search)"""
    try:
        data = request.json
        if not data:
            return jsonify({'status': 'error', 'message': 'No JSON data provided'}), 400
        
        material_code = data.get('material_code', '').strip()
        
        if not material_code:
            return jsonify({'status': 'error', 'message': 'Missing material_code'}), 400
        
        logger.info(f"Searching material by code: {material_code}")
        
        sap_validator = get_sap_validator()
        materials = sap_validator.search_material_by_code_only(material_code)
        
        response_data = {
            'status': 'success',
            'count': len(materials),
            'materials': materials,
            'message': 'Material found' if materials else 'Material not found'
        }
        
        logger.info(f"Search response for {material_code}: {response_data}")
        return jsonify(response_data)
        
    except Exception as e:
        logger.error(f"Search error: {e}")
        return jsonify({'status': 'error', 'message': str(e)}), 500

@app.route('/get_material_info', methods=['POST'])
@require_auth
def get_material_info():
    """Get material information from database or SAP"""
    try:
        data = request.json
        if not data:
            return jsonify({'status': 'error', 'message': 'No JSON data provided'}), 400
        
        material_code = data.get('material_code', '').strip()
        
        if not material_code:
            return jsonify({'status': 'error', 'message': 'Missing material_code'}), 400
        
        logger.info(f"Getting material info for: {material_code}")
        
        # Try to get from database first
        material_info = DatabaseHelper.get_material_info_from_db(material_code)
        
        if material_info:
            logger.info(f"Found material info in database: {material_code}")
            return jsonify({
                'status': 'success',
                'source': 'database',
                'material': material_info
            })
        
        # If not in database, try SAP
        sap_validator = get_sap_validator()
        sap_material_info = sap_validator.get_material_by_code(material_code)
        
        if sap_material_info:
            logger.info(f"Found material info in SAP: {material_code}")
            return jsonify({
                'status': 'success',
                'source': 'sap',
                'material': {
                    'material_code': sap_material_info.get('material_code', material_code),
                    'description': sap_material_info.get('description', ''),
                    'material_type': sap_material_info.get('material_type', 'N/A'),
                    'material_group': sap_material_info.get('material_group', 'N/A'),
                    'base_unit': sap_material_info.get('base_unit', 'N/A')
                }
            })
        
        # Material not found
        return jsonify({
            'status': 'error',
            'message': f'Material {material_code} not found in database or SAP'
        }), 404
        
    except Exception as e:
        logger.error(f"Get material info error: {e}")
        return jsonify({'status': 'error', 'message': str(e)}), 500

@app.route('/test_sap_connection', methods=['GET'])
@require_auth
def test_sap_connection():
    """Test SAP connection with detailed diagnostics"""
    try:
        if not PYRFC_AVAILABLE:
            return jsonify({
                'status': 'error',
                'message': 'pyrfc module not available. Please install SAP RFC connector.',
                'module_missing': True
            }), 500
        
        sap_validator = get_sap_validator()
        
        # Test network connectivity first
        network_ok = sap_validator._test_sap_connection()
        
        if not network_ok:
            return jsonify({
                'status': 'error',
                'message': f'Cannot reach SAP server {SAP_ASHOST}. Please check network connectivity.',
                'network_error': True,
                'sap_host': SAP_ASHOST,
                'sap_sysnr': SAP_SYSNR
            }), 500
        
        # Test RFC connection
        conn = None
        try:
            conn = sap_validator._create_connection()
            
            if not conn:
                return jsonify({
                    'status': 'error',
                    'message': f'Failed to establish SAP connection. Error: {sap_validator.connection_error}',
                    'connection_error': True,
                    'error_details': {
                        'status': sap_validator.connection_status,
                        'error': sap_validator.connection_error,
                        'last_attempt': sap_validator.last_connection_attempt.isoformat() if sap_validator.last_connection_attempt else None
                    },
                    'configuration': {
                        'ashost': SAP_ASHOST,
                        'sysnr': SAP_SYSNR,
                        'client': SAP_CLIENT,
                        'user': SAP_USER,
                        'lang': SAP_LANG
                    }
                }), 500
            
            # Test with RFC_PING
            try:
                test_result = conn.call('RFC_PING')
                
                result = jsonify({
                    'status': 'success',
                    'message': 'SAP connection test successful',
                    'ping_result': test_result,
                    'connection_info': {
                        'status': sap_validator.connection_status,
                        'last_connection': sap_validator.last_connection_attempt.isoformat() if sap_validator.last_connection_attempt else None
                    }
                })
                
                return result
                
            except RFCError as e:
                return jsonify({
                    'status': 'error',
                    'message': f'SAP RFC error: {e}',
                    'rfc_error': True
                }), 500
                
        finally:
            # Always close connection
            if conn:
                sap_validator._close_connection(conn)
            
    except Exception as e:
        logger.error(f"SAP connection test failed: {e}")
        return jsonify({
            'status': 'error',
            'message': f'SAP connection test failed: {str(e)}'
        }), 500

# ==================== UPLOAD ENDPOINTS ====================

@app.route('/upload_shop_drawing', methods=['POST'])
@require_auth
def upload_shop_drawing():
    """Upload shop drawing to Dropbox"""
    try:
        if 'drawing' not in request.files:
            return jsonify({'status': 'error', 'message': 'No drawing file provided'}), 400
        
        file = request.files['drawing']
        material_code = request.form.get('material_code')
        plant = request.form.get('plant', '')
        description = request.form.get('description', '')
        drawing_type = request.form.get('drawing_type', 'assembly')
        revision = request.form.get('revision', 'Rev0')  # Default to Rev0
        username = request.form.get('username', 'N/A')
        user_id = request.form.get('user_id', 0)  # PERBAIKAN: Ambil user_id dari form
        
        if not all([material_code, plant, description, revision]):
            return jsonify({'status': 'error', 'message': 'Missing required fields'}), 400
        
        if file.filename == '':
            return jsonify({'status': 'error', 'message': 'No selected file'}), 400
        
        allowed_extensions = {'jpg', 'jpeg', 'png', 'gif', 'bmp', 'pdf', 'dwg', 'dxf'}
        file_ext = file.filename.rsplit('.', 1)[1].lower() if '.' in file.filename else ''
        
        if file_ext not in allowed_extensions:
            return jsonify({'status': 'error', 'message': 'Invalid file type'}), 400
        
        max_size = 150 * 1024 * 1024  # 150MB for shop drawings
        file.seek(0, 2)
        file_size = file.tell()
        file.seek(0)
        
        if file_size > max_size:
            return jsonify({'status': 'error', 'message': f'File too large. Max size: 150MB, your file: {file_size/1024/1024:.2f}MB'}), 400
        
        # Validate drawing type
        allowed_drawing_types = ['assembly', 'detail', 'exploded', 'orthographic', 'perspective']
        if drawing_type not in allowed_drawing_types:
            return jsonify({'status': 'error', 'message': f'Invalid drawing type. Allowed: {", ".join(allowed_drawing_types)}'}), 400
        
        # Standardize revision
        from urllib.parse import unquote
        revision = unquote(revision)  # Decode URL-encoded characters
        
        # Remove any spaces, dashes, underscores and standardize format
        import re
        revision = re.sub(r'[\s\-_]+', '', revision)
        
        # Convert to standard RevX format
        if revision.lower() == 'master':
            revision = 'Rev0'
        elif revision.isdigit():
            revision = 'Rev' + str(int(revision))  # Remove leading zeros
        elif revision.lower().startswith('rev'):
            # Extract number and remove leading zeros
            match = re.search(r'\d+', revision)
            if match:
                num = match.group()
                revision = 'Rev' + str(int(num))
            else:
                revision = 'Rev0'
        else:
            # Try to extract any number
            match = re.search(r'\d+', revision)
            if match:
                num = match.group()
                revision = 'Rev' + str(int(num))
            else:
                revision = 'Rev0'
        
        # Check for duplicate in database before uploading
        if PYMYSQL_AVAILABLE:
            is_duplicate = DatabaseHelper.check_duplicate_drawing(material_code, plant, drawing_type, revision)
            if is_duplicate:
                return jsonify({
                    'status': 'error',
                    'message': f'A drawing with material code {material_code}, drawing type {drawing_type}, and revision {revision} already exists in the system.'
                }), 400
        
        # Validate material in SAP dan dapatkan material_type, material_group, base_unit
        material_info = None
        material_type = 'N/A'
        material_group = 'N/A'
        base_unit = 'N/A'
        
        try:
            sap_validator = get_sap_validator()
            material_info = sap_validator.get_material_by_code(material_code)
            if material_info:
                material_type = material_info.get('material_type', 'N/A')
                material_group = material_info.get('material_group', 'N/A')
                base_unit = material_info.get('base_unit', 'N/A')
                # Konversi ST ke PC jika diperlukan
                if base_unit == 'ST':
                    base_unit = 'PC'
            else:
                logger.warning(f"Material {material_code} not found in SAP, but continuing with upload")
        except Exception as sap_error:
            logger.warning(f"SAP validation skipped due to error: {sap_error}")
        
        file_content = file.read()
        
        dropbox_mgr = get_dropbox_manager()
        result = dropbox_mgr.upload_shop_drawing(
            file_content=file_content,
            filename=file.filename,
            material_code=material_code,
            plant=plant,
            description=description,
            drawing_type=drawing_type,
            revision=revision,
            username=username,
            user_id=user_id  # PERBAIKAN: Kirim user_id ke DropboxManager
        )
        
        if result['status'] == 'success':
            logger.info(f"Shop drawing uploaded successfully: {material_code}")
            
            # Update result dengan material info
            result.update({
                'uploaded_by': username,
                'drawing_type': drawing_type,
                'revision': revision,
                'material_type': material_type,
                'material_group': material_group,
                'base_unit': base_unit,
                'sap_material_info': material_info if material_info else None
            })
            
            return jsonify(result)
        else:
            return jsonify(result), 500
            
    except Exception as e:
        logger.error(f"Upload endpoint error: {e}", exc_info=True)
        return jsonify({'status': 'error', 'message': f'Server error: {e}'}), 500

# ==================== OTHER ENDPOINTS ====================

@app.route('/delete_shop_drawing', methods=['POST'])
@require_auth
def delete_shop_drawing():
    try:
        data = request.json
        if not data:
            return jsonify({'status': 'error', 'message': 'No JSON data provided'}), 400
        
        file_id = data.get('file_id')
        path = data.get('path')
        
        if not file_id or not path:
            return jsonify({'status': 'error', 'message': 'Missing file_id or path'}), 400
        
        dropbox_mgr = get_dropbox_manager()
        result = dropbox_mgr.delete_shop_drawing(file_id, path)
        return jsonify(result)
        
    except Exception as e:
        logger.error(f"Delete endpoint error: {e}")
        return jsonify({'status': 'error', 'message': str(e)}), 500

@app.route('/get_material_details', methods=['POST'])
@require_auth
def get_material_details():
    try:
        data = request.json
        if not data:
            return jsonify({'status': 'error', 'message': 'No JSON data provided'}), 400
        
        material_code = data.get('material_code')
        
        if not material_code:
            return jsonify({'status': 'error', 'message': 'Missing material_code'}), 400
        
        logger.info(f"Getting details for material: {material_code}")
        
        sap_validator = get_sap_validator()
        material_info = sap_validator.get_material_by_code(material_code)
        
        if not material_info:
            return jsonify({
                'status': 'error',
                'message': f'Material {material_code} not found in SAP'
            }), 404
        
        return jsonify({
            'status': 'success',
            'material': material_info
        })
        
    except Exception as e:
        logger.error(f"Get material details error: {e}")
        return jsonify({'status': 'error', 'message': str(e)}), 500

@app.route('/format_material_code', methods=['POST'])
@require_auth
def format_material_code():
    """Helper endpoint to format material code for SAP"""
    try:
        data = request.json
        if not data:
            return jsonify({'status': 'error', 'message': 'No JSON data provided'}), 400
        
        material_code = data.get('material_code')
        
        if not material_code:
            return jsonify({'status': 'error', 'message': 'Missing material_code'}), 400
        
        sap_validator = get_sap_validator()
        formatted_code = sap_validator.format_material_code_for_sap(material_code)
        
        return jsonify({
            'status': 'success',
            'original_code': material_code,
            'formatted_code': formatted_code,
            'is_numeric': material_code.isdigit(),
            'message': f'Numeric code padded to 18 digits' if material_code.isdigit() else 'Alphanumeric code used as-is'
        })
        
    except Exception as e:
        logger.error(f"Format material code error: {e}")
        return jsonify({'status': 'error', 'message': str(e)}), 500

@app.route('/test_dropbox_connection', methods=['GET'])
def test_dropbox_connection():
    try:
        dropbox_mgr = get_dropbox_manager()
        if dropbox_mgr.check_connection():
            return jsonify({
                'status': 'success',
                'message': 'Dropbox connection test successful'
            })
        else:
            return jsonify({
                'status': 'error',
                'message': 'Dropbox connection test failed. Please authenticate via OAuth.'
            }), 500
    except Exception as e:
        logger.error(f"Dropbox connection test failed: {e}")
        return jsonify({
            'status': 'error',
            'message': f'Dropbox connection test failed: {str(e)}'
        }), 500

@app.route('/refresh_dropbox_token', methods=['POST'])
@require_auth
def refresh_dropbox_token():
    """Manually refresh Dropbox token"""
    try:
        token_mgr = get_token_manager()
        if token_mgr.refresh_access_token():
            return jsonify({
                'status': 'success',
                'message': 'Dropbox token refreshed successfully',
                'new_token': token_mgr.get_access_token()[:20] + '...'
            })
        else:
            return jsonify({
                'status': 'error',
                'message': 'Failed to refresh Dropbox token. Need to authenticate via OAuth.'
            }), 500
    except Exception as e:
        logger.error(f"Manual token refresh error: {e}")
        return jsonify({'status': 'error', 'message': str(e)}), 500

@app.route('/get_token_status', methods=['GET'])
def get_token_status():
    """Get current token status"""
    try:
        token_mgr = get_token_manager()
        
        status = {
            'status': 'success',
            'has_access_token': bool(token_mgr.get_access_token()),
            'has_refresh_token': bool(token_mgr.get_refresh_token()),
            'token_preview': token_mgr.get_access_token()[:20] + '...' if token_mgr.get_access_token() else None,
            'needs_oauth': token_mgr.needs_oauth()
        }
        
        return jsonify(status)
    except Exception as e:
        logger.error(f"Token status error: {e}")
        return jsonify({'status': 'error', 'message': str(e)}), 500

# ==================== MAIN ====================

if __name__ == '__main__':
    required_vars = ['DROPBOX_APP_KEY', 'DROPBOX_APP_SECRET']
    missing_vars = [var for var in required_vars if not os.getenv(var)]
    
    if missing_vars:
        logger.error(f"Missing required environment variables: {missing_vars}")
        logger.info("Please create a new Dropbox app and add the credentials to .env file")
        logger.info("Visit: https://www.dropbox.com/developers/apps")
        sys.exit(1)
    
    sap_vars = ['SAP_ASHOST', 'SAP_USER', 'SAP_PASSWD']
    missing_sap_vars = [var for var in sap_vars if not os.getenv(var)]
    if missing_sap_vars:
        logger.warning(f"Missing SAP environment variables: {missing_sap_vars}")
        logger.warning("SAP functionality will be limited")
    
    port = int(os.getenv('DROPBOX_SERVICE_PORT', 5003))
    debug = os.getenv('FLASK_DEBUG', 'True').lower() == 'true'
    
    logger.info(f"Starting Shop Drawing Service on port {port}")
    logger.info(f"Dropbox App Key: {DROPBOX_APP_KEY[:5]}...")
    logger.info(f"Dropbox Access Token available: {'Yes' if DROPBOX_ACCESS_TOKEN else 'No'}")
    logger.info(f"Dropbox Refresh Token available: {'Yes' if DROPBOX_REFRESH_TOKEN else 'No'}")
    logger.info(f"SAP Host: {SAP_ASHOST}")
    logger.info(f"SAP User: {SAP_USER}")
    logger.info(f"API Token: {API_TOKEN[:10]}...")
    logger.info(f"Flask Secret Key: Generated and saved to {SECRET_KEY_FILE}")
    logger.info(f"Service URL: http://localhost:{port}")
    
    # Check module availability
    if not PYRFC_AVAILABLE:
        logger.warning("pyrfc module not available. SAP functionality will be disabled.")
    
    if not PYMYSQL_AVAILABLE:
        logger.warning("pymysql module not available. Database functionality will be disabled.")
    
    # Check initial Dropbox connection
    try:
        dropbox_mgr = get_dropbox_manager()
        if not dropbox_mgr.check_connection():
            logger.warning("Dropbox connection failed on startup!")
            logger.info(f"Please authenticate via OAuth by visiting: http://localhost:{port}/oauth/start")
    except Exception as e:
        logger.error(f"Dropbox initialization error: {e}")
        logger.info(f"Please authenticate via OAuth by visiting: http://localhost:{port}/oauth/start")
    
    app.run(
        host='0.0.0.0',
        port=port,
        debug=debug,
        threaded=True
    )