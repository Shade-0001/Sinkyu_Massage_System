<?php
echo "マッサージ版で必要なフィールド確認:\n\n";

echo "1. illness_name_other_text:\n";
echo "   → 傷病名「その他」の場合の自由記述欄\n";
echo "   → マッサージでも傷病名チェックボックスがあるなら必要\n\n";

echo "2. fee_initial_examination_amount:\n";
echo "   → 初検料（初診時の料金）\n";
echo "   → マッサージでも初検料は存在するため必要\n\n";

echo "3. health_center_registration_1/2:\n";
echo "   → 保健所登録番号（施術所の登録番号）\n";
echo "   → マッサージでも施術所登録は必要なため、必要な可能性あり\n\n";

echo "4. license_hari_number/license_kyu_number:\n";
echo "   → はり師・きゅう師の免許番号\n";
echo "   → マッサージ版では「あん摩マッサージ指圧師」の免許番号が必要\n";
echo "   → 座標がx=-59で負の値なので、PDF範囲外の可能性あり\n";
