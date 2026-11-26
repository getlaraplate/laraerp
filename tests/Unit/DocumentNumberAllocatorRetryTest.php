<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Modules\ERP\Services\Accounting\DocumentNumberAllocator;

function document_number_allocator_retries(?string $sql_state, int $driver_code, string $message): bool
{
    $previous = new PDOException($message);
    $previous->errorInfo = [$sql_state, $driver_code, $message];

    return new ReflectionMethod(DocumentNumberAllocator::class, 'isRetryableConcurrencyException')
        ->invoke(new DocumentNumberAllocator, new QueryException('db2', 'update erp_document_sequences set last_number = ?', [], $previous));
}

it('retries a Db2 lock timeout', function (?string $sql_state, int $driver_code, string $message): void {
    expect(document_number_allocator_retries($sql_state, $driver_code, $message))->toBeTrue();
})->with([
    'SQLSTATE 57033' => ['57033', -913, 'SQLSTATE[57033]: Deadlock or timeout occurred without automatic rollback'],
    'SQL0913 in the message' => ['HY000', -913, '[IBM][System i Access ODBC Driver][DB2 for i5/OS]SQL0913 - Row or object ERP_DOCUMENT_SEQUENCES in LARAPLATE type *FILE in use.'],
]);

it('does not retry an error that is not a lock conflict', function (): void {
    expect(document_number_allocator_retries('42501', -551, 'SQLSTATE[42501]: SQL0551 - Not authorized'))->toBeFalse();
});
