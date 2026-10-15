<?php

declare(strict_types=1);

return [
    'actions' => [
        'newSalesOrder' => 'Novo prodajno naročilo',
    ],
    'entities' => [
        'accounts' => [
            'singular' => 'Konto',
            'plural' => 'Kontni načrt',
        ],
        'bank_accounts' => [
            'singular' => 'Bančni račun',
            'plural' => 'Bančni računi',
        ],
        'bank_statements' => [
            'singular' => 'Bančni izpisek',
            'plural' => 'Bančni izpiski',
        ],
        'companies' => [
            'singular' => 'Podjetje',
            'plural' => 'Podjetja',
        ],
        'contacts' => [
            'singular' => 'Kontakt',
            'plural' => 'Kontakti',
        ],
        'customers' => [
            'plural' => 'Stranke',
            'singular' => 'Stranka',
        ],
        'delivery_notes' => [
            'singular' => 'Dobavnica',
            'plural' => 'Dobavnice',
        ],
        'document_sequences' => [
            'singular' => 'Številčenje dokumentov',
            'plural' => 'Številčenja dokumentov',
        ],
        'fiscal_periods' => [
            'singular' => 'Obračunsko obdobje',
            'plural' => 'Obračunska obdobja',
        ],
        'fiscal_years' => [
            'singular' => 'Poslovno leto',
            'plural' => 'Poslovna leta',
        ],
        'goods_receipts' => [
            'singular' => 'Prevzem blaga',
            'plural' => 'Prevzemi blaga',
        ],
        'invoices' => [
            'plural' => 'Računi',
            'singular' => 'Račun',
        ],
        'items' => [
            'plural' => 'Artikli',
            'singular' => 'Artikel',
        ],
        'journal_entries' => [
            'singular' => 'Knjižba',
            'plural' => 'Knjižbe',
        ],
        'leads' => [
            'singular' => 'Potencialna stranka',
            'plural' => 'Potencialne stranke',
        ],
        'movements' => [
            'singular' => 'Gibanje',
            'plural' => 'Gibanja',
        ],
        'opportunities' => [
            'singular' => 'Priložnost',
            'plural' => 'Priložnosti',
        ],
        'parties' => [
            'plural' => 'Partnerji',
            'singular' => 'Partner',
        ],
        'partner_pools' => [
            'singular' => 'Skupina partnerjev',
            'plural' => 'Skupine partnerjev',
        ],
        'payment_requests' => [
            'singular' => 'Zahtevek za plačilo',
            'plural' => 'Zahtevki za plačilo',
        ],
        'payment_runs' => [
            'singular' => 'Plačilni nalog',
            'plural' => 'Plačilni nalogi',
        ],
        'payment_terms' => [
            'singular' => 'Plačilni pogoj',
            'plural' => 'Plačilni pogoji',
        ],
        'payments' => [
            'singular' => 'Plačilo',
            'plural' => 'Plačila',
        ],
        'price_lists' => [
            'singular' => 'Cenik',
            'plural' => 'Ceniki',
        ],
        'projects' => [
            'plural' => 'Projekti',
            'singular' => 'Projekt',
        ],
        'purchase_orders' => [
            'plural' => 'Nabavna naročila',
            'singular' => 'Nabavno naročilo',
        ],
        'quotations' => [
            'plural' => 'Ponudbe',
            'singular' => 'Ponudba',
        ],
        'return_orders' => [
            'singular' => 'Vračilo kupca',
            'plural' => 'Vračila kupcev',
        ],
        'sales_orders' => [
            'plural' => 'Prodajna naročila',
            'singular' => 'Prodajno naročilo',
        ],
        'sites' => [
            'singular' => 'Lokacija',
            'plural' => 'Lokacije',
        ],
        'stock_levels' => [
            'singular' => 'Zaloga',
            'plural' => 'Zaloge',
        ],
        'supplier_returns' => [
            'singular' => 'Vračilo dobavitelju',
            'plural' => 'Vračila dobaviteljem',
        ],
        'tasks' => [
            'singular' => 'Opravilo',
            'plural' => 'Opravila',
        ],
        'tax_codes' => [
            'singular' => 'Davčna oznaka',
            'plural' => 'Davčne oznake',
        ],
        'vat_register_entries' => [
            'singular' => 'Vpis v knjigo DDV',
            'plural' => 'Vpisi v knjigo DDV',
        ],
        'vat_settlements' => [
            'singular' => 'Obračun DDV',
            'plural' => 'Obračuni DDV',
        ],
        'warehouses' => [
            'singular' => 'Skladišče',
            'plural' => 'Skladišča',
        ],
    ],
    'fields' => [
        'address_line' => 'Naslov',
        'amends_sales_order_id' => 'Popravlja naročilo',
        'city' => 'Mesto',
        'company_id' => 'Podjetje',
        'costing_method' => 'Metoda stroškov',
        'country' => 'Država',
        'created_at' => 'Ustvarjeno',
        'credited_invoice_id' => 'Dobropisani račun',
        'currency' => 'Valuta',
        'customer' => 'Stranka',
        'description' => 'Opis',
        'direction' => 'Smer',
        'einvoice_pec_email' => 'E-pošta PEC',
        'einvoice_recipient_code' => 'Koda prejemnika',
        'einvoice_transmission_format' => 'Format prenosa',
        'fiscal_country' => 'Davčna država',
        'invoice_type' => 'Vrsta',
        'is_customer' => 'Stranka',
        'is_supplier' => 'Dobavitelj',
        'journal_entry_id' => 'Knjižba',
        'name' => 'Ime',
        'notes' => 'Opombe',
        'number' => 'Številka',
        'opportunity_id' => 'Priložnost',
        'ordered_at' => 'Naročeno',
        'party' => 'Partner',
        'payment_term_id' => 'Plačilni pogoj',
        'postal_code' => 'Poštna številka',
        'posted_at' => 'Knjiženo',
        'project_id' => 'Projekt',
        'province' => 'Pokrajina',
        'quotation_id' => 'Ponudba',
        'reference' => 'Sklic',
        'revises_quotation_id' => 'Revidira ponudbo',
        'sku' => 'SKU',
        'status' => 'Stanje',
        'supplier' => 'Dobavitelj',
        'tax_id' => 'Davčna številka',
        'tracing_type' => 'Sledenje',
        'uom' => 'Merska enota',
        'updated_at' => 'Posodobljeno',
        'vat_number' => 'ID za DDV',
        'version' => 'Različica',
    ],
    'kpis' => [
        'openOrders' => 'Odprta naročila',
        'revenue' => 'Prihodki',
        'shipped' => 'Odposlano',
    ],
    'navigation' => [
        'accounting' => 'ERP - Računovodstvo',
        'inventory' => 'ERP - Zaloge',
        'master_data' => 'ERP - Matični podatki',
        'purchasing' => 'ERP - Nabava',
        'sales' => 'ERP - Prodaja',
        'treasury' => 'ERP - Zakladništvo',
    ],
    'sections' => [
        'address' => 'Naslov',
        'document' => 'Dokument',
        'einvoice' => 'Elektronsko izdajanje računov',
        'identity' => 'Identiteta',
        'links' => 'Povezani dokumenti',
        'notes' => 'Opombe',
        'order' => 'Naročilo',
        'project' => 'Projekt',
        'quotation' => 'Ponudba',
        'record' => 'Zapis',
        'stock' => 'Zaloga in stroški',
    ],
];
