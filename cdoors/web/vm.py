#!/usr/bin/python3
import argparse
import os
import subprocess
import json
import psycopg2
import sys

# Argument parser
parser = argparse.ArgumentParser(description='VM creation script.')
parser.add_argument('--vm-name', type=str, required=True, help='The name of the VM.')
parser.add_argument('--owner-id', type=int, required=True, help='The owner ID of the VM.')

args = parser.parse_args()

# Validate that VM name and owner ID are provided
if not args.vm_name or not args.owner_id:
    print(json.dumps({"status": "error", "message": "VM name or owner ID not provided."}))
    sys.exit(1)

vm_name = args.vm_name
owner_id = args.owner_id
template = "ubuntu-20.04"
OPENNEBULA_ENDPOINT = 'https://grid5.mif.vu.lt/cloud3/RPC2'

# Database details
DB_HOST = "10.0.1.236"
DB_NAME = 'testdb'
DB_USER = "user"
DB_PASSWORD = "pass"

# Connect to PostgreSQL
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

# Function to insert VM details into the database
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

env = dict(os.environ)
env['TERM'] = 'xterm'

# Run the VM creation command in OpenNebula
command = f"""
    onetemplate instantiate {template} --name {vm_name} --endpoint {OPENNEBULA_ENDPOINT}
    sleep 35
"""

try:
    # Execute the OpenNebula command to create a VM
    subprocess.run(command, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, env=env)

    # After VM creation, retrieve VM details
    vm_show_command = f"onevm show {vm_name} --endpoint {OPENNEBULA_ENDPOINT}"
    subprocess.run(vm_show_command, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, env=env)

    # Get the VM details (simulate the successful response for now)
    private_ip = "192.168.0.100"  # Example IP (replace this with real data extraction)
    connect_info1 = "user_login_info"  # Example connection info (replace this with real data extraction)

    # Insert VM details into the database
    db_response = insert_vm_details(owner_id=owner_id,
                                    user_login=connect_info1,
                                    vm_ssh_password="pass",
                                    private_ip=private_ip)

    if db_response['status'] == "error":
        print(json.dumps(db_response))
        sys.exit(1)  # Exit with code 1 if database insertion fails

    # If everything went well, return success
    response = {
        "status": "success",
        "message": f"VM {vm_name} created successfully!",
        "vm_id": vm_name,  # Example VM ID (replace with actual ID)
        "connect_info1": connect_info1,
        "private_ip": private_ip,
        "password": "pass"
    }
    print(json.dumps(response))
    sys.exit(0)  # Exit with code 0 for success

except Exception as e:
    response = {
        "status": "error",
        "message": f"Unexpected error: {str(e)}"
    }
    print(json.dumps(response))
    sys.exit(1)  # Exit with code 1 to indicate failure