#!/usr/bin/python3
import argparse
import os
import subprocess
import json
import psycopg2
import sys
import re
import json as json_file

def load_db_config():
    try:
        with open('/var/www/html/db_config.json') as f:
            config = json.load(f)
        return config
    except Exception as e:
        print(f"Error loading database config: {str(e)}")
        sys.exit(1)

parser = argparse.ArgumentParser(description='VM creation script.')
parser.add_argument('--vm-name', type=str, required=True, help='The name of the VM.')
parser.add_argument('--owner-id', type=int, required=True, help='The owner ID of the VM.')

args = parser.parse_args()

if not args.vm_name or not args.owner_id:
    print(json.dumps({"status": "error", "message": "VM name or owner ID not provided."}))
    sys.exit(1)

vm_name = args.vm_name
owner_id = args.owner_id
template = "ubuntu-20.04"
OPENNEBULA_ENDPOINT = 'https://grid5.mif.vu.lt/cloud3/RPC2'

db_config = load_db_config()

DB_HOST = db_config['host']
DB_NAME = db_config['database']
DB_USER = db_config['user']
DB_PASSWORD = db_config['password']

def get_db_connection():
    try:
        conn = psycopg2.connect(
            host=DB_HOST,
            database=DB_NAME,
            user=DB_USER,
            password=DB_PASSWORD
        )
        return conn
    except Exception as e:
        raise Exception(f"Failed to connect to the database: {str(e)}")

def insert_vm_details(owner_id, user_login, vm_ssh_password, private_ip):
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        insert_query = """
            INSERT INTO vm_details (ownerID, user_login, vm_ssh_password, private_ip)
            VALUES (%s, %s, %s, %s);
        """
        cursor.execute(insert_query, (owner_id, user_login, vm_ssh_password, private_ip))
        conn.commit()
        cursor.close()
        conn.close()
        return {"status": "success", "message": "VM details inserted into the database."}
    except Exception as e:
        return {"status": "error", "message": f"Database error: {str(e)}"}

# Function to check if a VM already exists for the owner_id
def check_existing_vm(owner_id):
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        query = "SELECT COUNT(*) FROM vm_details WHERE ownerID = %s"
        cursor.execute(query, (owner_id,))
        result = cursor.fetchone()
        cursor.close()
        conn.close()
        return result[0] > 0  # If count is greater than 0, a VM exists for this owner_id
    except Exception as e:
        raise Exception(f"Error while checking for existing VM: {str(e)}")

def get_vm_details(vm_name):
    vm_show_command = f"onevm show {vm_name} --endpoint {OPENNEBULA_ENDPOINT}"

    try:
        result = subprocess.run(vm_show_command, shell=True, capture_output=True, text=True)
        if result.returncode != 0:
            raise Exception(f"Failed to retrieve VM details: {result.stderr}")

        private_ip_match = re.search(r'PRIVATE_IP="([0-9\.]+)"', result.stdout)
        private_ip = private_ip_match.group(1) if private_ip_match else None

        connect_info_match = re.search(r'CONNECT_INFO1="([^"]+)"', result.stdout)
        connect_info1 = connect_info_match.group(1) if connect_info_match else None

        return private_ip, connect_info1

    except Exception as e:
        print(f"Error getting VM details: {str(e)}")
        return None, None

env = dict(os.environ)
env['TERM'] = 'xterm'

# Check if the user already has a VM
if check_existing_vm(owner_id):
    response = {
        "status": "error",
        "message": f"User with owner ID {owner_id} already has an existing VM."
    }
    print(json.dumps(response))
    sys.exit(1)  # Exit with code 1 if the user already has a VM

command = f"""
    onetemplate instantiate {template} --name {vm_name} --endpoint {OPENNEBULA_ENDPOINT}
    sleep 35
"""

try:
    subprocess.run(command, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, env=env)

    private_ip, connect_info1 = get_vm_details(vm_name)

    if not private_ip or not connect_info1:
        raise Exception("Failed to retrieve VM details after creation.")

    db_response = insert_vm_details(owner_id=owner_id,
                                    user_login=connect_info1,
                                    vm_ssh_password="pass",
                                    private_ip=private_ip)

    if db_response['status'] == "error":
        print(json.dumps(db_response))
        sys.exit(1)

    response = {
        "status": "success",
        "message": f"VM {vm_name} created successfully!",
        "vm_id": vm_name,
        "connect_info1": connect_info1,
        "private_ip": private_ip,
        "password": "pass"
    }
    print(json.dumps(response))
    sys.exit(0)

except Exception as e:
    response = {
        "status": "error",
        "message": f"Unexpected error: {str(e)}"
    }
    print(json.dumps(response))
    sys.exit(1)