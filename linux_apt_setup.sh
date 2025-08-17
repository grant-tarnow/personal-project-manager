sudo apt update
apt install libdbd-sqlite3-perl sqlite3

# TODO - make this editable to support multiple dbs
# Probably manage that within the perl script though
mkdir ~/.ppm
sqlite3 ~/.ppm/ppm.sqlite3 < schema.sql
