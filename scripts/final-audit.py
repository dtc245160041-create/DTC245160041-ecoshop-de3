#!/usr/bin/env python3
# Read-only EcoShop DE3 final audit: never modify or delete Docker volumes/database.
# Runs on Ubuntu with Python 3.6+, Docker Compose v2 and local BusyBox 1.36.
import datetime
import json
import os
import pathlib
import stat
import subprocess
import sys
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT=pathlib.Path(__file__).resolve().parent.parent
os.chdir(str(ROOT))
STAMP=datetime.datetime.now().strftime('%Y%m%d-%H%M%S')
OUT=ROOT/'evidence-de3'/STAMP
OUT.mkdir(parents=True,exist_ok=True)
os.chmod(str(OUT),0o700)
RESULTS=[]

def command(args,timeout=50):
    try:
        r=subprocess.run(args,stdout=subprocess.PIPE,stderr=subprocess.PIPE,
          universal_newlines=True,timeout=timeout,check=False)
        return r.returncode,r.stdout,r.stderr
    except Exception as exc:
        return 98,'',str(exc)

def dc(*args):
    return command(['sudo','docker','compose']+list(args))

def check(group,name,ok,note='',review=False):
    state='REVIEW' if review else ('PASS' if ok else 'FAIL')
    note=str(note).replace('\n',' ')[:280]
    RESULTS.append((group,name,state,note))
    print('[{}] {} / {} {}'.format(state,group,name,note))
    return ok

def get(url):
    try:
        # Query local Nginx with WordPress's canonical Host, so / does not
        # redirect to LAN and falsely report that the HTML is unavailable.
        request_headers={'User-Agent':'EcoShop-DE3-audit/1'}
        if url.startswith('http://127.0.0.1:18080'):
            request_headers['Host']='192.168.254.128:18080'
        req=urllib.request.Request(url,headers=request_headers)
        with urllib.request.urlopen(req,timeout=18) as r:
            return r.status,dict(r.headers.items()),r.read(160000)
    except urllib.error.HTTPError as exc:
        return exc.code,dict(exc.headers.items()),b''
    except Exception as exc:
        return 0,{},str(exc).encode('utf-8')

def service_id(name):
    code,out,err=dc('ps','-q',name)
    return out.strip() if code==0 else ''

print('ECOSHOP_DE3_FINAL_AUDIT',STAMP)
print('No SQL import, no volume deletion, no container restart.')

# Git and secret handling.
rc,stdout,err=command(['git','rev-list','--count','HEAD'])
n=int(stdout.strip()) if rc==0 and stdout.strip().isdigit() else 0
check('GITHUB','At least 3 commits',n>=3,'commits='+str(n))
rc,stdout,err=command(['git','ls-files','.env'])
check('GITHUB','Real .env not tracked',rc==0 and not stdout.strip())
check('GITHUB','Compose+README in repository',(ROOT/'compose.yaml').is_file() and (ROOT/'README.md').is_file())
envfile=ROOT/'.env'
if envfile.is_file():
    mode=stat.S_IMODE(envfile.stat().st_mode)
    check('HARDENING','Private .env permissions',(mode & 0o077)==0,oct(mode))
else:
    check('HARDENING','Private .env exists',False)

# Compose config must never be stored because it contains resolved secrets.
rc,text,err=dc('config','--format','json')
config=None
if rc==0:
    try: config=json.loads(text)
    except ValueError: pass
check('COMPOSE','Valid rendered Compose',config is not None,err[-100:] if rc else '')
if config:
    sv=config.get('services',{})
    volumes=config.get('volumes',{})
    networks=config.get('networks',{})
    check('COMPOSE','Isolated lab volumes',
      volumes.get('mysql_data',{}).get('name')=='de3_mysql_data' and
      volumes.get('wp_data',{}).get('name')=='de3_wp_data')
    check('HARDENING','Internal database network',bool(networks.get('database',{}).get('internal')))
    check('HARDENING','MySQL/Loki not exposed to host',
      not sv.get('mysql',{}).get('ports') and not sv.get('loki',{}).get('ports'))
    observed=('grafana','loki','promtail','prometheus','mysql-exporter')
    secure=all('no-new-privileges:true' in sv.get(name,{}).get('security_opt',[]) and
       'ALL' in sv.get(name,{}).get('cap_drop',[]) for name in observed)
    check('HARDENING','Privilege restrictions on monitoring',secure)
    check('HARDENING','Non-root Grafana and Loki',
      str(sv.get('grafana',{}).get('user'))=='472:472' and
      str(sv.get('loki',{}).get('user'))=='10001:10001')
    adminips=[]
    for name in ('phpmyadmin','grafana','prometheus'):
        for p in sv.get(name,{}).get('ports',[]):
            adminips.append(p.get('host_ip','') if isinstance(p,dict) else '')
    check('HARDENING','Administration ports limited to localhost',
          bool(adminips) and all(p=='127.0.0.1' for p in adminips))

needed=('mysql','wordpress','phpmyadmin','nginx','grafana','prometheus',
        'loki','promtail','node-exporter','cadvisor','mysql-exporter','nginx-exporter')
up=[]
for name in needed:
    ident=service_id(name)
    if ident:
        rc,stdout,err=command(['sudo','docker','inspect',ident,'--format','{{.State.Running}}'])
        if rc==0 and stdout.strip()=='true': up.append(name)
check('COMPOSE','All 12 application/observability services running',len(up)==len(needed),
      '{}/12 running'.format(len(up)))
rc,ps,err=dc('ps')
if rc==0: (OUT/'docker-compose-ps.txt').write_text(ps,encoding='utf-8')
ident=service_id('mysql')
if ident:
    rc,stdout,err=command(['sudo','docker','inspect',ident,'--format',
      '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}'])
    check('DATABASE','MySQL healthy',rc==0 and stdout.strip()=='healthy',stdout.strip())
else: check('DATABASE','MySQL healthy',False)

# HTTP and security.
for group,label,url in (
 ('WEBSITE','Website via Nginx','http://127.0.0.1:18080/'),
 ('DATABASE','phpMyAdmin','http://127.0.0.1:18081/'),
 ('GRAFANA','Grafana /api/health','http://127.0.0.1:13000/api/health'),
 ('PROMETHEUS','Prometheus /-/ready','http://127.0.0.1:19090/-/ready')):
    code,headers,body=get(url)
    ok=code==200
    if label=='Grafana /api/health' and ok:
        try: ok=json.loads(body.decode()).get('database')=='ok'
        except Exception: ok=False
    check(group,label,ok,'HTTP '+str(code))
code,headers,body=get('http://127.0.0.1:18080/')
headers={k.lower():v for k,v in headers.items()}
required=('x-content-type-options','x-frame-options','referrer-policy')
check('HARDENING','Three required security headers',
  all(headers.get(k) for k in required) and headers.get('x-content-type-options','').lower()=='nosniff')
(OUT/'nginx-security-headers.txt').write_text(
 '\n'.join(k+': '+headers.get(k,'MISSING') for k in
 ('x-content-type-options','x-frame-options','referrer-policy','permissions-policy',
  'content-security-policy'))+'\n',encoding='utf-8')
check('WEBSITE','EcoShop content loads',code==200 and b'EcoShop' in body)
check('WEBSITE','Add/edit/trash product through WordPress Admin',False,
  'Requires a real admin demo screenshot; no database write by audit.',review=True)
if ident:
    rc,tables,err=dc('exec','-T','mysql','sh','-c',
      'mysql -N -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" -e "SHOW TABLES;"')
    names=[t.strip() for t in tables.splitlines() if t.strip().startswith('wp_')]
    check('DATABASE','WordPress schema accessible as application user',
      rc==0 and all(t in names for t in ('wp_options','wp_posts','wp_users')),
      '{} WP tables'.format(len(names)))
    (OUT/'database-table-names.txt').write_text('\n'.join(names)+'\n',encoding='utf-8')

# Prometheus and Grafana provisioning.
code,_,body=get('http://127.0.0.1:19090/api/v1/targets')
targets=[]
if code==200:
    try: targets=json.loads(body.decode()).get('data',{}).get('activeTargets',[])
    except Exception: pass
target_summary=[(x.get('labels',{}).get('job','?'),x.get('health','?')) for x in targets]
check('PROMETHEUS','All monitoring targets UP',
      len(targets)>=5 and all(t.get('health')=='up' for t in targets),
      str(len(targets))+' targets')
(OUT/'prometheus-targets.txt').write_text(
  '\n'.join(job+': '+state for job,state in target_summary)+'\n',encoding='utf-8')
try:
    dashboard=json.loads((ROOT/'grafana/dashboards/ecoshop-de3.json').read_text(encoding='utf-8'))
    check('GRAFANA','Provisioned dashboard has panels',len(dashboard.get('panels',[]))>=5,
          str(len(dashboard.get('panels',[])))+' panels')
except Exception as exc:
    check('GRAFANA','Provisioned dashboard has panels',False,str(exc))
check('GRAFANA','Live charts visually checked',False,
      'Screenshot Grafana showing nonempty CPU/RAM/Disk and web/database panels.',review=True)

# Generate normal application traffic then query Loki using ephemeral BusyBox in the
# existing monitoring network. Loki has no public host port by design.
for path in ('/', '/san-pham/', '/__audit_404_'+STAMP):
    get('http://127.0.0.1:18080'+path+'?audit='+STAMP)
net=''
loki=service_id('loki')
if loki:
    rc,out,err=command(['sudo','docker','inspect',loki,'--format',
       '{{range $name,$v := .NetworkSettings.Networks}}{{println $name}}{{end}}'])
    for line in out.splitlines():
        if line.strip().endswith('_monitoring'): net=line.strip(); break
check('LOKI','Private monitoring network discovered',bool(net),net)
if net:
    def loki_http(uri):
        return command(['sudo','docker','run','--rm','--pull','never',
                        '--network',net,'busybox:1.36','wget','-qO-','-T','18',uri],timeout=40)
    rc,res,err=loki_http('http://loki:3100/ready')
    check('LOKI','Loki ready',rc==0 and res.strip()=='ready',err[-100:] if rc else res.strip()[:50])
    print('Waiting 12 seconds for Promtail to ship Nginx access logs...')
    time.sleep(12)
    now=int(time.time())
    start=str((now-900)*1000000000); end=str(now*1000000000)
    queries=[
      ('Q1 raw Nginx access','{job="nginx",site="ecoshop",log_type="access"}'),
      ('Q2 filtered GET','{job="nginx",site="ecoshop",log_type="access"} |= "GET "'),
      ('Q3 count_over_time 5m','sum(count_over_time({job="nginx",site="ecoshop",log_type="access"}[5m]))')
    ]
    results=[]
    for label,query in queries:
        uri='http://loki:3100/loki/api/v1/query_range?query='+urllib.parse.quote(query,safe='')+\
           '&start='+start+'&end='+end+'&step=30s&limit=100'
        rc,reply,err=loki_http(uri)
        series=0; valid=False
        if rc==0:
            try:
                data=json.loads(reply)
                series=len(data.get('data',{}).get('result',[]))
                valid=data.get('status')=='success' and series>0
            except Exception: pass
        note='series='+str(series) if rc==0 else 'request error '+err[-100:]
        check('LOKI',label,valid,note)
        results.append(label+' | '+query+' | '+('PASS' if valid else 'FAIL')+' | '+note)
    (OUT/'logql-query-results.txt').write_text('\n'.join(results)+'\n',encoding='utf-8')
else:
    check('LOKI','Three live LogQL queries',False,'Cannot query without internal monitoring network')
check('LOKI','Three Grafana Explore screenshots collected',False,
      'Use saved Q1-Q3 queries with time range Last 15 minutes.',review=True)
check('REPORT','Student cover data and manual screenshots finalized',False,
      'Student and faculty details/screenshots need final review.',review=True)

passed=sum(item[2]=='PASS' for item in RESULTS)
failed=sum(item[2]=='FAIL' for item in RESULTS)
pending=sum(item[2]=='REVIEW' for item in RESULTS)
lines=['# EcoShop - Final audit report','',
       'VM audit: '+datetime.datetime.now().strftime('%d/%m/%Y %H:%M:%S'),'',
       'PASS={} FAIL={} REVIEW={}'.format(passed,failed,pending),'',
       '| Criterion | Check | Status | Note |','|---|---|---|---|']
for group,name,status,note in RESULTS:
    lines.append('| {} | {} | {} | {} |'.format(group,name,status,note.replace('|','/')))
lines += ['','## Required manual screenshots','',
          '1. EcoShop homepage, catalog and WordPress product admin.',
          '2. phpMyAdmin MySQL WordPress tables.',
          '3. Nginx response with security headers.',
          '4. Prometheus targets and Grafana dashboard panels.',
          '5. Grafana Explore with all 3 LogQL queries and results.',
          '6. GitHub source, README and meaningful commits.',
          '',
          'REVIEW items are not automatically confirmed. Original data and volumes were not changed.']
(OUT/'bao-cao-nghiem-thu.md').write_text('\n'.join(lines)+'\n',encoding='utf-8')
print('\nFINAL_AUDIT_PASS={} FAIL={} REVIEW={}'.format(passed,failed,pending))
print('FINAL_AUDIT_REPORT='+str(OUT/'bao-cao-nghiem-thu.md'))
if failed:
    print('FINAL_AUDIT_NOT_PASSED - review the FAIL items; NEVER delete volumes.')
    sys.exit(1)
print('FINAL_AUDIT_AUTOMATED_OK - finish REVIEW items with real screenshots.')
