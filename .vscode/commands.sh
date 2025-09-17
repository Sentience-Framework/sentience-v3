mv ./.vscode/rector.php ./rector.php
./vendor/bin/rector
./vendor/bin/php-cs-fixer fix ./ --rules=no_unused_imports;
./vendor/bin/php-cs-fixer fix ./ --rules='{"@PSR12": true, "braces": {"position_after_functions_and_oop_constructs":"next","allow_single_line_closure":false}}';
php sentience.php dev-tools:sort-imports;
php sentience.php dev-tools:remove-trailing-commas;
php sentience.php dev-tools:remove-excessive-whitespace;
rm -rf .php-cs-fixer.cache
mv ./rector.php  ./.vscode/rector.php
