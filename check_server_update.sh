#!/bin/bash

echo "🔍 Проверка обновления кода на сервере..."
echo ""

# Проверяем, что файл TrendAgentParse.php содержит исправления
echo "1. Проверка логики пагинации в TrendAgentParse.php:"
if grep -q "(\$offset + \$count) < \$totalFromApi" /var/www/AL/app/Console/Commands/TrendAgentParse.php; then
    echo "   ✅ Исправления пагинации найдены"
else
    echo "   ❌ Исправления пагинации НЕ найдены"
fi

echo ""
echo "2. Проверка количества исправлений:"
COUNT=$(grep -c "(\$offset + \$count) < \$totalFromApi" /var/www/AL/app/Console/Commands/TrendAgentParse.php)
echo "   Найдено исправлений: $COUNT (ожидается: 7)"

echo ""
echo "3. Проверка последнего коммита:"
cd /var/www/AL
git log --oneline -1

echo ""
echo "4. Проверка статуса Git:"
git status

echo ""
echo "✅ Проверка завершена"
