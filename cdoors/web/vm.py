#!/usr/bin/python3
import os
import subprocess
import json
import time
import cgi

env = dict(os.environ)
env['TERM'] = 'xterm'

form = cgi.FieldStorage()
vm_name = form.getvalue("vm_name")
template = form.getvalue("template")

# Hardcode the OpenNebula endpoint
OPENNEBULA_ENDPOINT = 'https://grid5.mif.vu.lt/cloud3/RPC2'

print("Content-Type: application/json\n")

command = f"""
        onetemplate instantiate {template} --name {vm_name} --endpoint {OPENNEBULA_ENDPOINT}
        sleep 35
        """

try:
    result = subprocess.run(command, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, env=env)

    stdout = result.stdout.strip()
    stderr = result.stderr.strip()

    vm_id = None
    for line in stdout.splitlines():
        if "VM ID:" in line:
            vm_id = line.split(":")[1].strip()

    if stderr:
        response = {
            "status": "error",
            "message": f"Failed to create VM. Error: {stderr}"
        }
    else:
        if vm_id:
            vm_show_command = f"onevm show {vm_id} --endpoint {OPENNEBULA_ENDPOINT}"
            vm_show_result = subprocess.run(vm_show_command, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, env=env)

            vm_show_stdout = vm_show_result.stdout.strip()
            vm_show_stderr = vm_show_result.stderr.strip()

            private_ip = None
            connect_info1 = None

            for line in vm_show_stdout.splitlines():
                if "PRIVATE_IP" in line:
                    private_ip = line.split("=")[1].strip().replace('"', '')

                if "CONNECT_INFO1" in line:
                    connect_info1 = line.split("=")[1].strip().replace('"', '')

            if vm_show_stderr:
                response = {
                    "status": "error",
                    "message": f"Failed to retrieve VM details. Error: {vm_show_stderr}"
                }
            else:
                response = {
                    "status": "success",
                    "message": f"VM {vm_name} created successfully!",
                    "vm_id": vm_id,
                    "connect_info1": connect_info1,
                    "private_ip": private_ip,
                    "password": "pass"
                }

        else:
            response = {
                "status": "error",
                "message": "VM creation process did not return expected information."
            }

    print(json.dumps(response))

except Exception as e:
    response = {
        "status": "error",
        "message": f"Unexpected error: {str(e)}"
    }
    print(json.dumps(response))
