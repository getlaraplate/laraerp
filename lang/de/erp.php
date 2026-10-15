<?php

declare(strict_types=1);

return [
    'actions' => [
        'newSalesOrder' => 'Neuer Kundenauftrag',
    ],
    'entities' => [
        'accounts' => [
            'singular' => 'Konto',
            'plural' => 'Kontenplan',
        ],
        'bank_accounts' => [
            'singular' => 'Bankkonto',
            'plural' => 'Bankkonten',
        ],
        'bank_statements' => [
            'singular' => 'Kontoauszug',
            'plural' => 'Kontoauszüge',
        ],
        'companies' => [
            'singular' => 'Unternehmen',
            'plural' => 'Unternehmen',
        ],
        'contacts' => [
            'singular' => 'Kontakt',
            'plural' => 'Kontakte',
        ],
        'customers' => [
            'plural' => 'Kunden',
            'singular' => 'Kunde',
        ],
        'delivery_notes' => [
            'singular' => 'Lieferschein',
            'plural' => 'Lieferscheine',
        ],
        'document_sequences' => [
            'singular' => 'Belegnummernkreis',
            'plural' => 'Belegnummernkreise',
        ],
        'fiscal_periods' => [
            'singular' => 'Buchungsperiode',
            'plural' => 'Buchungsperioden',
        ],
        'fiscal_years' => [
            'singular' => 'Geschäftsjahr',
            'plural' => 'Geschäftsjahre',
        ],
        'goods_receipts' => [
            'singular' => 'Wareneingang',
            'plural' => 'Wareneingänge',
        ],
        'invoices' => [
            'plural' => 'Rechnungen',
            'singular' => 'Rechnung',
        ],
        'items' => [
            'plural' => 'Artikel',
            'singular' => 'Artikel',
        ],
        'journal_entries' => [
            'singular' => 'Buchung',
            'plural' => 'Buchungen',
        ],
        'leads' => [
            'singular' => 'Lead',
            'plural' => 'Leads',
        ],
        'movements' => [
            'singular' => 'Bewegung',
            'plural' => 'Bewegungen',
        ],
        'opportunities' => [
            'singular' => 'Verkaufschance',
            'plural' => 'Verkaufschancen',
        ],
        'parties' => [
            'plural' => 'Geschäftspartner',
            'singular' => 'Geschäftspartner',
        ],
        'partner_pools' => [
            'singular' => 'Partnerpool',
            'plural' => 'Partnerpools',
        ],
        'payment_requests' => [
            'singular' => 'Zahlungsanforderung',
            'plural' => 'Zahlungsanforderungen',
        ],
        'payment_runs' => [
            'singular' => 'Zahlungslauf',
            'plural' => 'Zahlungsläufe',
        ],
        'payment_terms' => [
            'singular' => 'Zahlungsbedingung',
            'plural' => 'Zahlungsbedingungen',
        ],
        'payments' => [
            'singular' => 'Zahlung',
            'plural' => 'Zahlungen',
        ],
        'price_lists' => [
            'singular' => 'Preisliste',
            'plural' => 'Preislisten',
        ],
        'projects' => [
            'plural' => 'Projekte',
            'singular' => 'Projekt',
        ],
        'purchase_orders' => [
            'plural' => 'Bestellungen',
            'singular' => 'Bestellung',
        ],
        'quotations' => [
            'plural' => 'Angebote',
            'singular' => 'Angebot',
        ],
        'return_orders' => [
            'singular' => 'Kundenretoure',
            'plural' => 'Kundenretouren',
        ],
        'sales_orders' => [
            'plural' => 'Kundenaufträge',
            'singular' => 'Kundenauftrag',
        ],
        'sites' => [
            'singular' => 'Standort',
            'plural' => 'Standorte',
        ],
        'stock_levels' => [
            'singular' => 'Lagerbestand',
            'plural' => 'Lagerbestände',
        ],
        'supplier_returns' => [
            'singular' => 'Lieferantenretoure',
            'plural' => 'Lieferantenretouren',
        ],
        'tasks' => [
            'singular' => 'Aufgabe',
            'plural' => 'Aufgaben',
        ],
        'tax_codes' => [
            'singular' => 'Steuerschlüssel',
            'plural' => 'Steuerschlüssel',
        ],
        'vat_register_entries' => [
            'singular' => 'Umsatzsteuerbuchung',
            'plural' => 'Umsatzsteuerbuchungen',
        ],
        'vat_settlements' => [
            'singular' => 'Umsatzsteuervoranmeldung',
            'plural' => 'Umsatzsteuervoranmeldungen',
        ],
        'warehouses' => [
            'singular' => 'Lager',
            'plural' => 'Lager',
        ],
    ],
    'fields' => [
        'address_line' => 'Adresse',
        'amends_sales_order_id' => 'Ändert Auftrag',
        'city' => 'Stadt',
        'company_id' => 'Gesellschaft',
        'costing_method' => 'Kalkulationsmethode',
        'country' => 'Land',
        'created_at' => 'Erstellt',
        'credited_invoice_id' => 'Gutgeschriebene Rechnung',
        'currency' => 'Währung',
        'customer' => 'Kunde',
        'description' => 'Beschreibung',
        'direction' => 'Richtung',
        'einvoice_pec_email' => 'PEC-E-Mail',
        'einvoice_recipient_code' => 'Empfängercode',
        'einvoice_transmission_format' => 'Übertragungsformat',
        'fiscal_country' => 'Steuerland',
        'invoice_type' => 'Typ',
        'is_customer' => 'Kunde',
        'is_supplier' => 'Lieferant',
        'journal_entry_id' => 'Buchungssatz',
        'name' => 'Name',
        'notes' => 'Notizen',
        'number' => 'Nummer',
        'opportunity_id' => 'Verkaufschance',
        'ordered_at' => 'Bestellt am',
        'party' => 'Geschäftspartner',
        'payment_term_id' => 'Zahlungsbedingung',
        'postal_code' => 'Postleitzahl',
        'posted_at' => 'Gebucht am',
        'project_id' => 'Projekt',
        'province' => 'Provinz',
        'quotation_id' => 'Angebot',
        'reference' => 'Referenz',
        'revises_quotation_id' => 'Überarbeitet Angebot',
        'sku' => 'SKU',
        'status' => 'Status',
        'supplier' => 'Lieferant',
        'tax_id' => 'Steuernummer',
        'tracing_type' => 'Rückverfolgung',
        'uom' => 'Maßeinheit',
        'updated_at' => 'Aktualisiert',
        'vat_number' => 'USt-IdNr.',
        'version' => 'Version',
    ],
    'kpis' => [
        'openOrders' => 'Offene Aufträge',
        'revenue' => 'Umsatz',
        'shipped' => 'Versendet',
    ],
    'navigation' => [
        'accounting' => 'ERP - Buchhaltung',
        'inventory' => 'ERP - Lager',
        'master_data' => 'ERP - Stammdaten',
        'purchasing' => 'ERP - Einkauf',
        'sales' => 'ERP - Verkauf',
        'treasury' => 'ERP - Finanzen',
    ],
    'sections' => [
        'address' => 'Adresse',
        'document' => 'Dokument',
        'einvoice' => 'Elektronische Rechnung',
        'identity' => 'Identität',
        'links' => 'Verknüpfte Dokumente',
        'notes' => 'Notizen',
        'order' => 'Auftrag',
        'project' => 'Projekt',
        'quotation' => 'Angebot',
        'record' => 'Datensatz',
        'stock' => 'Bestand und Kalkulation',
    ],
];
