#!/bin/bash

# Script para probar los endpoints de la API
BASE_URL="http://tuabogadoenlinea.free.nf"

echo "=== 1. Probando endpoint de información ==="
curl -X GET "$BASE_URL/api/" | jq .

echo -e "\n=== 2. Listando planes disponibles ==="
curl -X GET "$BASE_URL/api/planes.php" | jq .

echo -e "\n=== 3. Registrando un cliente ==="
curl -X POST "$BASE_URL/api/auth.php?action=register" \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "Juan Pérez",
    "email": "juan@example.com",
    "contraseña": "password123",
    "rol": "cliente"
  }' | jq .

echo -e "\n=== 4. Haciendo login ==="
curl -X POST "$BASE_URL/api/auth.php?action=login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "juan@example.com",
    "contraseña": "password123"
  }' | jq .

echo -e "\n=== Pruebas completadas ==="

