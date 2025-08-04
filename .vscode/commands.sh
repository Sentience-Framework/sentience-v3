./vendor/bin/rector
php-cs-fixer fix ./ --rules=no_unused_imports;
php-cs-fixer fix ./ --rules='{"@PSR12": true, "braces": {"position_after_functions_and_oop_constructs":"next","allow_single_line_closure":false}}';
php sentience.php dev-tools:sort-imports;
php sentience.php dev-tools:remove-trailing-commas;
php sentience.php dev-tools:remove-excessive-whitespace;
rm -rf .php-cs-fixer.cache
