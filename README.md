# 🛡️ Home SOC Lab — Automated Threat Detection with Wazuh SIEM

> A hands-on Security Operations Center simulation built on a physical machine running multiple virtual machines connected via a home LAN.  
> Demonstrates real-world detection of brute force attacks, privilege escalation, web attacks, file integrity violations and more — with automated response.

![Wazuh](https://img.shields.io/badge/SIEM-Wazuh%204.x-blue?style=flat-square)
![Platform](https://img.shields.io/badge/Platform-Linux%20%7C%20Windows-lightgrey?style=flat-square)
![Lab](https://img.shields.io/badge/Type-Physical%20Home%20Lab-green?style=flat-square)
![Status](https://img.shields.io/badge/Status-Active-brightgreen?style=flat-square)

---

## 📌 Project Overview

This project simulates a **mini Security Operations Center (SOC)** using open-source tools across two physical machines connected over a real LAN. The goal was to build, configure, and validate a working SIEM pipeline — from log ingestion to automated threat response.

Unlike typical portfolio projects that rely entirely on virtual machines, this lab uses a **physical bare-metal endpoint** as a monitored agent, making the network traffic and detection scenarios more realistic and closer to real enterprise environments.

**Key focus areas:**
- Centralized log collection from Linux and Windows endpoints (Apache, Sysmon, Auditd, Windows Defender)
- Real-time alert generation using default and custom Wazuh detection rules
- File Integrity Monitoring (FIM) for critical system paths and web server files
- Web attack simulation and detection (directory fuzzing, login brute force)
- Deep Windows endpoint telemetry using Sysmon with sysmon-modular configuration

---

## 🖥️ Hardware Setup

This lab runs on a **physical host machine** running multiple virtual machines connected via a home router (LAN). No cloud services or external hosting required.

### Machine  — Primary Workstation (Wazuh Server + Attacker)
| Component | Spec |
|---|---|
| CPU | AMD Ryzen 5 7600 |
| RAM | 32 GB |
| GPU | AMD RX 9070 |
| OS | Windows 11 (host) |
| Virtualization | VMware — runs Wazuh Server VM + Kali Linux VM + Ubuntu Linux agent VM|

---

### Components Summary
| Host            | Role                      | OS                | Type                        |
| --------------- | ------------------------- | ----------------- | --------------------------- |
| `wazuh-server`  | Wazuh Manager + Dashboard | Ubuntu 24.04 LTS  | Virtual Machine             |
| `kali-attacker` | Attack simulation         | Kali Linux 2025.x | Virtual Machine             |
| `windows-agent` | Monitored endpoint + Sysmon | Windows 11      | **Physical (host machine)** |
| `linux-agent`   | Monitored endpoint + Apache | Ubuntu 24.04 LTS | Virtual Machine            |

## 🎯 Attack Scenarios & Detection Results

### Scenario 1 — SSH Brute Force (Hydra)
**Attack:** Hydra launched login attempts against the Linux agent over SSH.
```bash
# Brute force SSH against Linux agent
hydra -l root -P /usr/share/wordlists/rockyou.txt -t 4 ssh://
```
**Detection:** Multiple failed SSH authentication attempts
**Result:** Alert — *"SSH brute force attack detected"* — source IP and attempt count logged to dashboard.
<img width="1652" height="442" alt="image" src="https://github.com/user-attachments/assets/24952491-f2b8-43bf-b4cc-47fe92458389" />
<img width="650" height="245" alt="image" src="https://github.com/user-attachments/assets/c713571b-ecfa-4dfb-95b7-cbdc036b449d" />


---

### Scenario 2 — Directory Fuzzing (ffuf)
**Attack:** ffuf used to discover hidden directories and files on the Apache web server.
```bash
# Directory fuzzing with dirb wordlist
ffuf -u http:///192.168.0.0/FUZZ -w /usr/share/wordlists/dirb/big.txt
```
**Detection:** Large number of HTTP 400 errors from same source IP 
**Result:** Alert — *"Multiple web server 400 error codes from same source IP"* — Level 10.

<img width="1632" height="437" alt="image" src="https://github.com/user-attachments/assets/9418cc2b-e448-4ba1-a76f-dda6bb84cc41" />
<img width="658" height="144" alt="image" src="https://github.com/user-attachments/assets/af74e038-2043-4be7-bb01-c0e6c771fbbb" />



---

### Scenario 3 — Web Login Brute Force (Hydra)
**Attack:** Hydra used to brute force the login form on a custom PHP login page running on Apache.
```bash
hydra -l admin -P /usr/share/wordlists/rockyou.txt 192.168.0.0 http-post-form "/login.php:username=^USER^&password=^PASS^:401"
```
**Detection:** Multiple HTTP 401 responses from same source IP detected in Apache access logs → Wazuh alert triggered automatically.  
**Result:** Alert — *"Web server 400 error code"* `.
<img width="1173" height="634" alt="image" src="https://github.com/user-attachments/assets/6ac34bfc-5b4a-42a7-b6ce-c68c91117351" />




---
### Scenario 4 — Windows Defender Disabled
**Attack:** Windows Defender real-time protection disabled manually — simulating an attacker trying to disable endpoint protection before deploying malware.
```powershell
Set-MpPreference -DisableRealtimeMonitoring $true
```
**Detection:** Windows Defender event forwarded to Wazuh.  
**Result:** Alert — *"Windows Defender real-time protection disabled"*.  
**Response:** Re-enable protection after test:
```powershell
Set-MpPreference -DisableRealtimeMonitoring $false
```
<img width="1634" height="167" alt="image" src="https://github.com/user-attachments/assets/b58cd860-679c-4094-a7a2-fe46216fa510" />

## 🚀 How to Reproduce

### Prerequisites
- One physical machine (host) running VMware Workstation
- Ubuntu 24.04 LTS Server ISO + Kali Linux 2025.x ISO
- All virtual machines running on VMware with Bridged network adapter

### Step 1 — Install Wazuh Server (Primary machine VM)
```bash
curl -sO https://packages.wazuh.com/4.14/wazuh-install.sh && sudo bash ./wazuh-install.sh -a
```
To open dashboard paste your ip in browser
<img width="1646" height="900" alt="image" src="https://github.com/user-attachments/assets/739b9e35-6ae2-4d07-a31f-a92c7d214566" />

### Step 2 — Deploy and configure Windows Agent (Physical main machine)
```bash
Invoke-WebRequest -Uri https://packages.wazuh.com/4.x/windows/wazuh-agent-4.14.4-1.msi -OutFile $env:tmp\wazuh-agent; msiexec.exe /i $env:tmp\wazuh-agent /q WAZUH_MANAGER='**SERVER IP' WAZUH_AGENT_NAME='**NAME'
Manually verify the <address> field in ossec.conf. If it is set to 0.0.0.0, replace it with the correct Wazuh manager IP or hostname.
# Download Sysmon + sysmon-modular config (https://github.com/olafhartong/sysmon-modular/blob/master/sysmonconfig.xml)
cd C:\Users\Yourname\Downloads
.\Sysmon64.exe -accepteula -i sysmonconfig.xml
Use my .conf file for windows
```
<img width="1620" height="691" alt="image" src="https://github.com/user-attachments/assets/4d2958b1-42dc-43ed-a247-49006a60230e" />


### Step 3 — Deploy and configure Linux Agent/Server
```bash
wget https://packages.wazuh.com/4.x/apt/pool/main/w/wazuh-agent/wazuh-agent_4.14.4-1_amd64.deb && sudo WAZUH_MANAGER='192.168.0.236' dpkg -i ./wazuh-agent_4.14.4-1_amd64.deb
sudo systemctl daemon-reload
sudo systemctl enable wazuh-agent
sudo systemctl start wazuh-agent


## Add Auditd — log every command

sudo apt install auditd -y
sudo systemctl enable auditd
sudo systemctl start auditd


## Deploy Apache Web Server (attack target)

sudo apt install apache2 -y
sudo systemctl enable apache2
sudo systemctl start apache2

Add FIM — Monitor web server and system files
use my .conf file for linux 

Add login.php to /var/www/html

sudo systemctl restart wazuh-agent 
```

<img width="1190" height="774" alt="image" src="https://github.com/user-attachments/assets/45721be5-db73-4f6f-98b9-80daf8d353ee" />
<img width="1647" height="45" alt="image" src="https://github.com/user-attachments/assets/20ce18d4-95c8-4f1d-a986-d39dd63a71a7" />



### Step 6 — Run Attack Simulations (from Kali VM)


---

## 🛠️ Tools & Technologies
| Tool | Purpose |
|---|---|
| **Wazuh 4.14** | SIEM — log collection, alerting |
| **OpenSearch + Dashboards** | Log indexing and visualization |
| **Sysmon + sysmon-modular** | Deep Windows endpoint telemetry |
| **Windows Defender** | Endpoint AV — logs forwarded to Wazuh |
| **Apache2** | Web server — attack target for web-based scenarios |
| **Hydra** | SSH brute force simulation |
| **ffuf** | Web directory fuzzing |
| **nmap** | Network port scanning |
| **Auditd** | Linux command logging |
| **VirtualBox / VMware** | Virtualization on primary machine |
| **Ubuntu 24.04 LTS / Kali 2025.x / Windows 11** | Operating systems |

---

## 📚 What I Learned

Building this lab from scratch gave me hands-on experience with tools and concepts 
that are used daily in real SOC environments:

- **SIEM fundamentals** — how Wazuh collects and processes logs from different 
  operating systems (Linux and Windows) and displays them in one central dashboard
- **Agent deployment and configuration** — deploying and tuning Wazuh agents on 
  both Linux and Windows, including custom `ossec.conf` configuration for different 
  log sources (Apache, Sysmon, Auditd, Windows Defender)
- **Attack tool usage** — practical experience with Hydra, ffuf, Burp Suite 
  and understanding how each attack appears in logs and what signatures it leaves
- **Log analysis** — reading and interpreting raw logs from auth.log, Apache 
  access logs, Windows Event Logs and Sysmon to understand what happened during an attack
- **False positive investigation** — identifying and triaging alerts that turned out 
  to be legitimate system behavior (e.g. OneDrive process injection, AppArmor denials)
- **General SOC workflow** — understanding the full cycle: attack happens → logs 
  generated → SIEM collects them → alert triggered → analyst investigates
---

## 🗺️ Future Improvements

- [ ] Add Suricata IDS for network-level detection (NIDS)
- [ ] Integrate SOARs for automated execution
- [ ] Simulate lateral movement between endpoints
- [ ] Set up Slack/email alerting for critical severity events
- [ ] Map all attack scenarios to MITRE ATT&CK framework
- [ ] Automate Wazuh agent deployment across endpoints using Ansible from the Wazuh Server VM

---

## 📄 References

- [Wazuh Official Documentation](https://documentation.wazuh.com)
- [Wazuh Active Response Guide](https://documentation.wazuh.com/current/user-manual/capabilities/active-response/)
- [MITRE ATT&CK Framework](https://attack.mitre.org)

---

*Built as a personal cybersecurity portfolio project. All attack simulations were performed in an isolated lab environment on privately owned hardware.*
