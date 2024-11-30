#!/bin/bash

update_system() {
    echo "<---> Updating and upgrading system."
    apt-get update -y && apt-get dist-upgrade -y
}

install_ansible_ubuntu() {

    if dpkg -l | grep -qw ansible; then
        echo "<---> Ansible is already installed. Skipping installation."

    else
        echo "<---> Installing Ansible and dependencies."
        apt install -y software-properties-common
        add-apt-repository --yes --update ppa:ansible/ansible
        apt update -y
        apt install -y ansible-core ansible
    fi

    echo "<---> Installing Ansible collection for PostgreSQL."
    ansible-galaxy collection install community.postgresql
}

install_opennebula_tools() {
    if dpkg -l | grep -qw opennebula-tools; then
        echo "<---> Opennebula-tools is already installed. Skipping installation."
        return
    fi

    echo "<---> Adding OpenNebula repository and installing tools."
    wget -q -O- https://downloads.opennebula.org/repo/repo.key | sudo apt-key add -
    echo "deb https://downloads.opennebula.org/repo/5.6/Ubuntu/18.04 stable opennebula" > /etc/apt/sources.list.d/opennebula.list
    apt update -y
    apt-get install -y opennebula-tools
}

install_python_and_pip() {
    if ! dpkg -l | grep -qw python3; then
        echo "<---> Installing Python 3."
        apt install -y python3
    else
        echo "<---> Python 3 is already installed."
    fi

    if ! dpkg -l | grep -qw python3-pip; then
        echo "<---> Installing pip."
        apt install -y python3-pip
    else
        echo "<---> Pip is already installed."
    fi
}

install_pyone() {
    echo "<---> Installing pyone."
    pip install pyone==6.8.3
}

create_vms() {
    local ansible_main="ansible_main.yml"
    ansible-playbook "$PLAYBOOK_DIR/$ansible_main" -i "$INVENTORY_FILE" -vvv --ask-vault-pass
}

main() {
    SCRIPT_DIR="$(pwd)"
    PLAYBOOK_DIR="$SCRIPT_DIR/../ansible"
    INVENTORY_FILE="$PLAYBOOK_DIR/hosts"

    update_system
    install_python_and_pip

    install_ansible_ubuntu
    install_opennebula_tools
    install_pyone

    create_vms
}

main
exit 0
