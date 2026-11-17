#!/usr/bin/env bash
# Garante o banco de testes do Pest sem tocar no banco do demo.
set -e

export PGPASSWORD=db

if ! psql -h db -U db -d postgres -tAc "SELECT 1 FROM pg_database WHERE datname = 'db_test'" | grep -q 1; then
    psql -h db -U db -d postgres -c 'CREATE DATABASE db_test'
fi
