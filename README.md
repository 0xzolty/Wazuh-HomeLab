# 🛡️ Home SOC Lab — Automated Threat Detection with Wazuh SIEM

> A hands-on Security Operations Center simulation built across two physical machines connected via a home LAN.  
> Demonstrates real-world detection of brute force attacks, privilege escalation, port scanning, and file integrity violations — with automated response.

![Wazuh](https://img.shields.io/badge/SIEM-Wazuh%204.x-blue?style=flat-square)
![Platform](https://img.shields.io/badge/Platform-Linux%20%7C%20Windows-lightgrey?style=flat-square)
![Lab](https://img.shields.io/badge/Type-Physical%20Home%20Lab-green?style=flat-square)
![Status](https://img.shields.io/badge/Status-Active-brightgreen?style=flat-square)

---

## 📌 Project Overview

This project simulates a **mini Security Operations Center (SOC)** using open-source tools across two physical machines connected over a real LAN. The goal was to build, configure, and validate a working SIEM pipeline — from log ingestion to automated threat response.

Unlike typical portfolio projects that rely entirely on virtual machines, this lab uses a **physical bare-metal endpoint** as a monitored agent, making the network traffic and detection scenarios more realistic and closer to real enterprise environments.

**Key focus areas:**
- Centralized log collection from a physical Linux endpoint
- Real-time alert generation based on custom detection rules
- Automated Active Response (IP blocking) triggered by detected attacks
- Threat enrichment via VirusTotal API integration
- File Integrity Monitoring (FIM) for critical system paths

---

## 🖥️ Hardware Setup

This lab runs across **two physical machines** connected to the same home router (LAN). No cloud services or external hosting required.

### Machine 1 — Primary Workstation (Wazuh Server + Attacker)
| Component | Spec |
|---|---|
| CPU | AMD Ryzen 5 7600 |
| RAM | 32 GB |
| GPU | AMD RX 9070 |
| OS | Windows 11 (host) |
| Virtualization | VirtualBox — runs Wazuh Server VM + Kali Linux VM |

### Machine 2 — Secondary Workstation (Physical Agent)
| Component | Spec |
|---|---|
| CPU | Intel Core i5-8600K |
| RAM | 8 GB |
| GPU | NVIDIA GTX 1660 |
| OS | Ubuntu 24.04 LTS (dual boot — bare metal) |
| Role | Physical Wazuh agent — monitored Linux endpoint |

---

## 🏗️ Architecture

```
Home Router (192.168.1.1)
        │
        ├── Machine 1: Ryzen 5 7600 — Windows 11 (192.168.1.10)
        │       │
        │       ├── [VM] Wazuh Server — Ubuntu 24.04 (192.168.1.20)
        │       │         Wazuh Manager + Indexer + Dashboard
        │       │
        │       └── [VM] Kali Linux 2025.x (192.168.1.30)
        │                 Attack simulation machine
        │
        └── Machine 2: i5-8600K — Ubuntu 24.04 bare metal (192.168.1.40)
                  Physical Wazuh Agent — monitored Linux endpoint
```
<img width="1203" height="600" alt="image" src="https://github.com/user-attachments/assets/cdd6cdd6-7266-4970-b321-20e4f7b4d1ec" />


> Real network traffic flows between physical machines through the home router,  
> making attack detection more realistic compared to host-only VM networks.

### Components Summary

| Host            | Role                      | OS                | Type                        |
| --------------- | ------------------------- | ----------------- | --------------------------- |
| `wazuh-server`  | Wazuh Manager + Dashboard | Ubuntu 24.04 LTS  | Virtual Machine             |
| `kali-attacker` | Attack simulation         | Kali Linux 2025.x | Virtual Machine             |
| `windows-agent` | Monitored endpoint        | Windows 11        | **Physical (host machine)** |
| `linux-agent`   | Monitored endpoint        | Ubuntu 24.04 LTS  | **Physical **               |


## ⚙️ Configuration Highlights

### 1. File Integrity Monitoring (FIM)
Configured to monitor critical Linux paths in real time:

```xml
<syscheck>
  <directories check_all="yes" realtime="yes">/etc,/usr/bin,/usr/sbin</directories>
  <directories check_all="yes" realtime="yes">/bin,/sbin,/boot</directories>
</syscheck>
```

### 3. Active Response — Automatic IP Block
SSH brute force triggers automatic firewall block via `iptables`:

```xml
<active-response>
  <command>firewall-drop</command>
  <location>local</location>
  <rules_id>5763</rules_id>
  <timeout>600</timeout>
</active-response>
```

### 4. VirusTotal Integration
File hashes from FIM alerts are automatically checked against VirusTotal:

```xml
<integration>
  <n>virustotal</n>
  <api_key>YOUR_API_KEY</api_key>
  <rule_id>554,550</rule_id>
  <alert_format>json</alert_format>
</integration>
```

---

## 🎯 Attack Scenarios & Detection Results

### Scenario 1 — SSH Brute Force (Hydra)
**Attack:** Hydra launched 200 login attempts from Kali VM against the physical Linux agent over real LAN.  
**Detection:** Wazuh rule `5763` triggered after 8 failed attempts in 10 seconds.  
**Response:** Attacker IP automatically blocked via `firewall-drop` Active Response.

```
Alert Level: 10
Rule: Authentication brute force (5763)
Source IP: 192.168.1.30 (Kali VM)
Target: 192.168.1.40 (physical agent)
Action: IP blocked for 600 seconds
```

---

### Scenario 2 — Port Scanning (nmap)
**Attack:** Full TCP SYN scan (`nmap -sS -p-`) from Kali VM against the physical Linux agent.  
**Detection:** Wazuh correlated multiple connection drops → rule `40111` triggered.  
**Result:** Alert with source IP, scan type, and timestamp logged to dashboard.

---

### Scenario 3 — Privilege Escalation
**Attack:** New user added to `/etc/sudoers` manually on the physical agent.  
**Detection:** FIM alert triggered on `/etc/sudoers` modification + custom rule `100001`.  
**Result:** Level 12 critical alert with file diff and responsible user.

---

### Scenario 4 — Malicious File Drop (VirusTotal)
**Attack:** Known malware sample (EICAR test file) dropped into monitored directory on physical agent.  
**Detection:** FIM detected new file → hash sent to VirusTotal → flagged as malicious.  
**Result:** Alert enriched with VT detection ratio (e.g., `58/72 engines`).

---
### Scenario X — Windows Defender Disabled
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


## 📊 Dashboard Screenshots

| View | Description |
|---|---|
| `screenshots/overview.png` | Security events overview — 24h |
| `screenshots/brute_force_alert.png` | Brute force detection + active response log |
| `screenshots/fim_alert.png` | File integrity alert with diff |
| `screenshots/virustotal.png` | VirusTotal enrichment on suspicious file |

---

## 🚀 How to Reproduce

### Prerequisites
- Two physical machines connected to the same LAN
- VirtualBox 7.x installed on the primary machine
- Ubuntu 24.04 LTS ISO + Kali Linux 2025.x ISO
- Ubuntu 24.04 LTS installed bare metal on the secondary machine (dual boot)

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
sudo cp index.html /var/www/html/index.html

Add FIM — Monitor web server and system files

sudo systemctl restart wazuh-agent

use my .conf file for linux 
```

<img width="1190" height="774" alt="image" src="https://github.com/user-attachments/assets/45721be5-db73-4f6f-98b9-80daf8d353ee" />
<img width="1647" height="45" alt="image" src="https://github.com/user-attachments/assets/20ce18d4-95c8-4f1d-a986-d39dd63a71a7" />



### Step 6 — Run Attack Simulations (from Kali VM)
```bash
#Directory Fuzzing (ffuf) with dirb wordlist

ffuf -u http:///FUZZ -w /usr/share/wordlists/dirb/big.txt

<img width="1632" height="437" alt="image" src="https://github.com/user-attachments/assets/9418cc2b-e448-4ba1-a76f-dda6bb84cc41" />
<img width="658" height="144" alt="image" src="https://github.com/user-attachments/assets/af74e038-2043-4be7-bb01-c0e6c771fbbb" />

# Brute force SSH against physical agent
hydra -l root -P /usr/share/wordlists/rockyou.txt ssh://192.168.1.40

# Port scan physical agent
nmap -sS -p- 192.168.1.40

# Simulate privilege escalation (run on physical agent)
sudo echo "attacker ALL=(ALL) NOPASSWD:ALL" >> /etc/sudoers
```

---

## 🛠️ Tools & Technologies

| Tool | Purpose |
|---|---|
| **Wazuh 4.7** | SIEM — log collection, alerting, Active Response |
| **OpenSearch + Dashboards** | Log indexing and visualization |
| **Hydra** | SSH brute force simulation |
| **nmap** | Network port scanning |
| **VirusTotal API** | Threat intelligence enrichment |
| **VirtualBox** | Virtualization on primary machine |
| **Ubuntu 24.04 LTS / Kali 2025.x** | Operating systems |
| **Sysmon + sysmon-modular** | Deep Windows endpoint telemetry |

---

## 📚 What I Learned

- How a SIEM ingests and normalizes logs from heterogeneous endpoints across a real network
- Writing and tuning custom detection rules to reduce false positives
- How Active Response works as an automated first line of defense
- Difference between virtual and physical (bare metal) agent deployment
- Integrating threat intelligence APIs into the alerting pipeline
- Network-level considerations when connecting physical machines to a SIEM

---

## 🗺️ Future Improvements

- [ ] Add Suricata IDS for network-level detection (NIDS)
- [ ] Integrate Shuffle SOAR for automated playbook execution
- [ ] Add TheHive for incident management and case tracking
- [ ] Simulate lateral movement between endpoints
- [ ] Set up Slack/email alerting for critical severity events
- [ ] Map all attack scenarios to MITRE ATT&CK framework
- [ ] Deploy a honeypot (OpenCanary) to catch internal recon

---

## 📄 References

- [Wazuh Official Documentation](https://documentation.wazuh.com)
- [Wazuh Active Response Guide](https://documentation.wazuh.com/current/user-manual/capabilities/active-response/)
- [VirusTotal API Docs](https://developers.virustotal.com/reference)
- [MITRE ATT&CK Framework](https://attack.mitre.org)

---

*Built as a personal cybersecurity portfolio project. All attack simulations were performed in an isolated lab environment on privately owned hardware.*
