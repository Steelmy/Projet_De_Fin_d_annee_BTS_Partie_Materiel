#!/bin/sh
# Remise à zéro de la base de démonstration : supprime la base puis rejoue le dump d'origine.
# Seule la base est touchée (pas les volumes Caddy, donc pas de nouveau certificat HTTPS).
#
#   sh /reset-db.sh       → tourne en boucle et remet à zéro aux heures de RESET_TIMES ("04:00 13:00")
#   sh /reset-db.sh now   → remise à zéro immédiate, une seule fois
#
# Variables : DB_NAME, DB_ROOT_PASS, RESET_TIMES ; le dump est monté en /dump.sql.

export MYSQL_PWD="$DB_ROOT_PASS"  # lu par le client mariadb : le mot de passe n'apparaît pas dans `ps`

reset_db() {
    echo "$(date '+%d/%m/%Y %H:%M:%S') Remise à zéro de la base $DB_NAME..."
    if mariadb -h db -u root -e "DROP DATABASE IF EXISTS \`$DB_NAME\`" &&
       mariadb -h db -u root --default-character-set=utf8mb4 < /dump.sql; then
        echo "$(date '+%d/%m/%Y %H:%M:%S') Base remise à zéro."
    else
        echo "$(date '+%d/%m/%Y %H:%M:%S') ÉCHEC de la remise à zéro." >&2
        return 1
    fi
}

# Timestamp de la prochaine heure de RESET_TIMES (aujourd'hui si elle n'est pas passée, sinon demain).
next_reset() {
    now=$(date +%s)
    best=""
    for t in $RESET_TIMES; do
        ts=$(date -d "today $t" +%s) || return 1
        [ "$ts" -le "$now" ] && ts=$(date -d "tomorrow $t" +%s)
        if [ -z "$best" ] || [ "$ts" -lt "$best" ]; then best=$ts; fi
    done
    echo "$best"
}

if [ "${1:-}" = "now" ]; then
    reset_db
    exit $?
fi

# Sans trap, le shell (PID 1 du conteneur) ignorerait SIGTERM et `docker compose down` attendrait 10 s.
trap 'exit 0' TERM INT

while true; do
    target=$(next_reset)
    if [ -z "$target" ]; then
        echo "RESET_TIMES invalide (« $RESET_TIMES ») : format attendu \"04:00 13:00\"." >&2
        exit 1
    fi
    echo "Prochaine remise à zéro : $(date -d "@$target" '+%d/%m/%Y à %H:%M')"
    # sleep en arrière-plan + wait : permet au trap de s'exécuter pendant l'attente.
    sleep $((target - $(date +%s))) &
    wait $!
    reset_db
done
