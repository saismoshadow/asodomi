# Guida Git — Progetto ASODOMI

Repo: https://github.com/saismoshadow/asodomi
Branch principale: `main`
Cartella locale sul server: `/var/www/asodomi`

---

## Comandi di uso quotidiano

```bash
cd /var/www/asodomi

# 1) Vedi cosa è cambiato
git status

# 2) Aggiungi i file modificati (o tutti con "-A")
git add -A

# 3) Crea il commit con un messaggio chiaro
git commit -m "Breve descrizione di cosa hai fatto"

# 4) Invia le modifiche su GitHub
git push
```

> Suggerimento messaggio: `git commit -m "Aggiunto..."`, `"Corretto bug..."`, `"Fix: data iscrizione"`.

---

## Riportare indietro (annullare) le modifiche

### Ultima modifica non ancora committata
```bash
git restore .          # scarta tutte le modifiche non salvate
```

### Ultimo commit — solo messaggio/restore di lavoro sporco
```bash
git reset --soft HEAD~1   # annulla l'ultimo commit ma TIENI le modifiche
```

### Ultimo commit — eliminalo del tutto (ATTENZIONE: perde le modifiche)
```bash
git reset --hard HEAD~1
```

### Tornare a un commit passato specifico
```bash
git log --oneline        # elenca i commit con i loro ID
git checkout <ID> .      # ripristina i file a quel commit (staged)
# oppure, per esplorare senza salvare:
git checkout <ID>        # ti sposta su quel commit (detached HEAD)
```

### Annullare un commit GIÀ pubblicato su GitHub (sconsigliato se collabori)
```bash
git revert <ID>          # crea un nuovo commit che annulla il precedente
git push                 # poi vai in remoto senza "forzare"
```

> ⚠️ `reset --hard` elimina davvero le modifiche. Usa `revert` se il commit è già su GitHub e altri ci lavorano.

---

## Salvataggio periodico (backup su cloud)
Git + GitHub fungono da **backup**: ogni `commit` + `push` salva uno snapshot sul cloud.
Fai `git add -A` → `git commit -m "..."` → `git push` ogni volta che cambi qualcosa di importante.

---

## Collaborare con altre persone

### Altri sviluppatori
Per unirti al progetto da un altro computer:
```bash
git clone https://github.com/saismoshadow/asodomi.git
cp config.example.php inc/config.php   # poi inserisci i tuoi dati
```

### Piccoli team (workflow base)
1. Prima di iniziare a lavorare: `git pull` (prendi le ultime modifiche)
2. Fai le tue modifiche → `git add` → `git commit` → `git push`
3. Se `push` viene rifiutato (qualcun altro ha modificato): `git pull --rebase` poi di nuovo `git push`

### Team più strutturato (consigliato per più persone)
1. Crea un ramo per ogni modifica: `git checkout -b feature-nome`
2. Lavora e committa sul ramo
3. `git push -u origin feature-nome`
4. Su GitHub apri una **Pull Request** verso `main`
5. Dopo la revisione e il merge, elimina il ramo

### Chi può collaborare
- Essendo un repo **pubblico**, serve essere invitati come collaboratore: **GitHub → Settings → Collaborators → Add people**

---

## File gestiti con attenzione

### `inc/config.php` — NON è su GitHub
- Contiene credenziali DB e dati di contatto (è in `.gitignore`)
- **Ogni ambiente deve crearlo** da `config.example.php`
- Modificare direttamente sul server, mai sul repo

### `uploads/` — NON è su GitHub
- Contiene i file caricati dai soci (privati)
- Resta solo sul server, incluso nel backup locale (`/root/backup_asodomi`)

### File residui esclusi
`install.php`, `router-dev.php`, `index.html_old`, `NOTE.md` — non committare.

---

## Backup a parte (massima sicurezza)
Git NON salva `config.php` e `uploads/`. Per un backup TOTALE fai anche:
```bash
tar czf /root/backup_asodomi/asodomi_backup_$(date +%F).tar.gz -C /var/www asodomi
```
(vedi anche `NOTE.md` per il ripristino DB+file)
