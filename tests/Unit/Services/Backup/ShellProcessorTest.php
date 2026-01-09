<?php

use App\Services\Backup\ShellProcessor;

test('sanitizes sensitive patterns', function (string $input, string $expectedToContain, string $secretToRedact) {
    $processor = new ShellProcessor;

    $result = $processor->sanitize($input);

    expect($result)
        ->toContain($expectedToContain)
        ->not->toContain($secretToRedact);
})->with([
    '--password= format' => ['mysqldump --password=secret123 dbname', '--password=***', 'secret123'],
    'quoted --password= format' => ["mysqldump --password='secret123' dbname", '--password=***', 'secret123'],
    '-p shorthand format' => ['mysqldump -psecret123 dbname', '-p***', 'secret123'],
    'PGPASSWORD env var' => ['PGPASSWORD=secret123 pg_dump dbname', 'PGPASSWORD=***', 'secret123'],
    'MYSQL_PWD env var' => ['MYSQL_PWD=secret123 mysqldump failed', 'MYSQL_PWD=***', 'secret123'],
    'mysql connection string' => ['Failed to connect to mysql://user:secret123@localhost:3306/db', 'mysql://user:***@localhost', 'secret123'],
    'postgresql connection string' => ['postgresql://admin:mypassword@db.example.com:5432/mydb', 'postgresql://admin:***@db.example.com', 'mypassword'],
    'AWS_SECRET_ACCESS_KEY' => ['AWS_SECRET_ACCESS_KEY=wJalrXUtnFEMI', 'AWS_SECRET_ACCESS_KEY=***', 'wJalrXUtnFEMI'],
    'AWS_ACCESS_KEY_ID' => ['AWS_ACCESS_KEY_ID=AKIAIOSFODNN7EXAMPLE', 'AWS_ACCESS_KEY_ID=***', 'AKIAIOSFODNN7EXAMPLE'],
    'api_key token' => ['api_key=sk-12345', 'api_key=***', 'sk-12345'],
    'secret token' => ['secret=xyz789', 'secret=***', 'xyz789'],
]);

test('preserves non-sensitive patterns', function (string $input, string $expectedToContain) {
    $processor = new ShellProcessor;

    $result = $processor->sanitize($input);

    expect($result)->toContain($expectedToContain);
})->with([
    'hostname containing -p' => ["mysqldump --host='mysql-production.example.com' dbname", 'mysql-production.example.com'],
    '--port option' => ['mysqldump --port=3306 dbname', '--port=3306'],
    '-p with space (port flag)' => ['pg_dump -p 5432 dbname', '-p 5432'],
]);

test('sanitizes realistic mariadb-dump command', function () {
    $processor = new ShellProcessor;

    $command = "mariadb-dump --host='mysql-production.example.com' --port='3306' --user='root' --password='supersecret' 'mydb'";
    $result = $processor->sanitize($command);

    expect($result)
        ->toContain('mysql-production.example.com')
        ->toContain("--port='3306'")
        ->toContain('--password=***')
        ->not->toContain('supersecret');
});

test('sanitizes realistic pg_dump command', function () {
    $processor = new ShellProcessor;

    $command = 'PGPASSWORD=supersecret pg_dump -h postgres-production.example.com -p 5432 -U admin mydb';
    $result = $processor->sanitize($command);

    expect($result)
        ->toContain('PGPASSWORD=***')
        ->toContain('postgres-production.example.com')
        ->toContain('-p 5432')
        ->not->toContain('supersecret');
});
