<?php

try {
    $pdo = new PDO(
        "pgsql:host=aws-1-sa-east-1.pooler.supabase.com;port=6543;dbname=postgres;sslmode=require",
        "postgres.jbmjbkzmldemsnuzrgou",
        "Orbe@2026%5ADS"
    );

    echo "Conectou!";
} catch (PDOException $e) {
    echo $e->getMessage();
}