<?php

declare(strict_types=1);

return [
    'actions' => [
        'newSalesOrder' => 'Nuovo ordine di vendita',
    ],
    'entities' => [
        'accounts' => [
            'singular' => 'Conto',
            'plural' => 'Piano dei conti',
        ],
        'bank_accounts' => [
            'singular' => 'Conto bancario',
            'plural' => 'Conti bancari',
        ],
        'bank_statements' => [
            'singular' => 'Estratto conto',
            'plural' => 'Estratti conto',
        ],
        'companies' => [
            'singular' => 'Azienda',
            'plural' => 'Aziende',
        ],
        'contacts' => [
            'singular' => 'Contatto',
            'plural' => 'Contatti',
        ],
        'customers' => [
            'plural' => 'Clienti',
            'singular' => 'Cliente',
        ],
        'delivery_notes' => [
            'singular' => 'Documento di trasporto',
            'plural' => 'Documenti di trasporto',
        ],
        'document_sequences' => [
            'singular' => 'Numeratore documenti',
            'plural' => 'Numeratori documenti',
        ],
        'fiscal_periods' => [
            'singular' => 'Periodo contabile',
            'plural' => 'Periodi contabili',
        ],
        'fiscal_years' => [
            'singular' => 'Esercizio',
            'plural' => 'Esercizi',
        ],
        'goods_receipts' => [
            'singular' => 'Ricevimento merci',
            'plural' => 'Ricevimenti merci',
        ],
        'invoices' => [
            'plural' => 'Fatture',
            'singular' => 'Fattura',
        ],
        'items' => [
            'plural' => 'Articoli',
            'singular' => 'Articolo',
        ],
        'journal_entries' => [
            'singular' => 'Registrazione contabile',
            'plural' => 'Registrazioni contabili',
        ],
        'leads' => [
            'singular' => 'Lead',
            'plural' => 'Lead',
        ],
        'movements' => [
            'singular' => 'Movimento',
            'plural' => 'Movimenti',
        ],
        'opportunities' => [
            'singular' => 'Opportunità',
            'plural' => 'Opportunità',
        ],
        'parties' => [
            'plural' => 'Anagrafiche',
            'singular' => 'Anagrafica',
        ],
        'partner_pools' => [
            'singular' => 'Pool di partner',
            'plural' => 'Pool di partner',
        ],
        'payment_requests' => [
            'singular' => 'Richiesta di pagamento',
            'plural' => 'Richieste di pagamento',
        ],
        'payment_runs' => [
            'singular' => 'Distinta di pagamento',
            'plural' => 'Distinte di pagamento',
        ],
        'payment_terms' => [
            'singular' => 'Termine di pagamento',
            'plural' => 'Termini di pagamento',
        ],
        'payments' => [
            'singular' => 'Pagamento',
            'plural' => 'Pagamenti',
        ],
        'price_lists' => [
            'singular' => 'Listino',
            'plural' => 'Listini',
        ],
        'projects' => [
            'plural' => 'Progetti',
            'singular' => 'Progetto',
        ],
        'purchase_orders' => [
            'plural' => 'Ordini di acquisto',
            'singular' => 'Ordine di acquisto',
        ],
        'quotations' => [
            'plural' => 'Offerte',
            'singular' => 'Offerta',
        ],
        'return_orders' => [
            'singular' => 'Reso cliente',
            'plural' => 'Resi clienti',
        ],
        'sales_orders' => [
            'plural' => 'Ordini di vendita',
            'singular' => 'Ordine di vendita',
        ],
        'sites' => [
            'singular' => 'Sede',
            'plural' => 'Sedi',
        ],
        'stock_levels' => [
            'singular' => 'Giacenza',
            'plural' => 'Giacenze',
        ],
        'supplier_returns' => [
            'singular' => 'Reso a fornitore',
            'plural' => 'Resi a fornitori',
        ],
        'tasks' => [
            'singular' => 'Attività',
            'plural' => 'Attività',
        ],
        'tax_codes' => [
            'singular' => 'Codice IVA',
            'plural' => 'Codici IVA',
        ],
        'vat_register_entries' => [
            'singular' => 'Registrazione IVA',
            'plural' => 'Registrazioni IVA',
        ],
        'vat_settlements' => [
            'singular' => 'Liquidazione IVA',
            'plural' => 'Liquidazioni IVA',
        ],
        'warehouses' => [
            'singular' => 'Magazzino',
            'plural' => 'Magazzini',
        ],
    ],
    'fields' => [
        'address_line' => 'Indirizzo',
        'amends_sales_order_id' => 'Rettifica l\'ordine',
        'city' => 'Città',
        'company_id' => 'Società',
        'costing_method' => 'Metodo di costing',
        'country' => 'Paese',
        'created_at' => 'Creato',
        'credited_invoice_id' => 'Fattura stornata',
        'currency' => 'Valuta',
        'customer' => 'Cliente',
        'description' => 'Descrizione',
        'direction' => 'Direzione',
        'einvoice_pec_email' => 'Email PEC',
        'einvoice_recipient_code' => 'Codice destinatario',
        'einvoice_transmission_format' => 'Formato di trasmissione',
        'fiscal_country' => 'Paese fiscale',
        'invoice_type' => 'Tipo',
        'is_customer' => 'Cliente',
        'is_supplier' => 'Fornitore',
        'journal_entry_id' => 'Scrittura contabile',
        'name' => 'Nome',
        'notes' => 'Note',
        'number' => 'Numero',
        'opportunity_id' => 'Opportunità',
        'ordered_at' => 'Ordinato il',
        'party' => 'Anagrafica',
        'payment_term_id' => 'Termine di pagamento',
        'postal_code' => 'CAP',
        'posted_at' => 'Registrato il',
        'project_id' => 'Progetto',
        'province' => 'Provincia',
        'quotation_id' => 'Offerta',
        'reference' => 'Riferimento',
        'revises_quotation_id' => 'Rivede l\'offerta',
        'sku' => 'SKU',
        'status' => 'Stato',
        'supplier' => 'Fornitore',
        'tax_id' => 'Codice fiscale',
        'tracing_type' => 'Tracciabilità',
        'uom' => 'Unità di misura',
        'updated_at' => 'Aggiornato',
        'vat_number' => 'Partita IVA',
        'version' => 'Versione',
    ],
    'kpis' => [
        'openOrders' => 'Ordini aperti',
        'revenue' => 'Fatturato',
        'shipped' => 'Spediti',
    ],
    'navigation' => [
        'accounting' => 'ERP - Contabilità',
        'inventory' => 'ERP - Magazzino',
        'master_data' => 'ERP - Anagrafiche',
        'purchasing' => 'ERP - Acquisti',
        'sales' => 'ERP - Vendite',
        'treasury' => 'ERP - Tesoreria',
    ],
    'sections' => [
        'address' => 'Indirizzo',
        'document' => 'Documento',
        'einvoice' => 'Fatturazione elettronica',
        'identity' => 'Anagrafica',
        'links' => 'Documenti collegati',
        'notes' => 'Note',
        'order' => 'Ordine',
        'project' => 'Progetto',
        'quotation' => 'Offerta',
        'record' => 'Record',
        'stock' => 'Magazzino e costi',
    ],
];
