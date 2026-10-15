<?php

declare(strict_types=1);

return [
    'actions' => [
        'newSalesOrder' => 'New sales order',
    ],
    'entities' => [
        'accounts' => [
            'singular' => 'Account',
            'plural' => 'Chart of accounts',
        ],
        'bank_accounts' => [
            'singular' => 'Bank account',
            'plural' => 'Bank accounts',
        ],
        'bank_statements' => [
            'singular' => 'Bank statement',
            'plural' => 'Bank statements',
        ],
        'companies' => [
            'singular' => 'Company',
            'plural' => 'Companies',
        ],
        'contacts' => [
            'singular' => 'Contact',
            'plural' => 'Contacts',
        ],
        'customers' => [
            'plural' => 'Customers',
            'singular' => 'Customer',
        ],
        'delivery_notes' => [
            'singular' => 'Delivery note',
            'plural' => 'Delivery notes',
        ],
        'document_sequences' => [
            'singular' => 'Document sequence',
            'plural' => 'Document sequences',
        ],
        'fiscal_periods' => [
            'singular' => 'Fiscal period',
            'plural' => 'Fiscal periods',
        ],
        'fiscal_years' => [
            'singular' => 'Fiscal year',
            'plural' => 'Fiscal years',
        ],
        'goods_receipts' => [
            'singular' => 'Goods receipt',
            'plural' => 'Goods receipts',
        ],
        'invoices' => [
            'plural' => 'Invoices',
            'singular' => 'Invoice',
        ],
        'items' => [
            'plural' => 'Items',
            'singular' => 'Item',
        ],
        'journal_entries' => [
            'singular' => 'Journal entry',
            'plural' => 'Journal entries',
        ],
        'leads' => [
            'singular' => 'Lead',
            'plural' => 'Leads',
        ],
        'movements' => [
            'singular' => 'Movement',
            'plural' => 'Movements',
        ],
        'opportunities' => [
            'singular' => 'Opportunity',
            'plural' => 'Opportunities',
        ],
        'parties' => [
            'plural' => 'Parties',
            'singular' => 'Party',
        ],
        'partner_pools' => [
            'singular' => 'Partner pool',
            'plural' => 'Partner pools',
        ],
        'payment_requests' => [
            'singular' => 'Payment request',
            'plural' => 'Payment requests',
        ],
        'payment_runs' => [
            'singular' => 'Payment run',
            'plural' => 'Payment runs',
        ],
        'payment_terms' => [
            'singular' => 'Payment term',
            'plural' => 'Payment terms',
        ],
        'payments' => [
            'singular' => 'Payment',
            'plural' => 'Payments',
        ],
        'price_lists' => [
            'singular' => 'Price list',
            'plural' => 'Price lists',
        ],
        'projects' => [
            'plural' => 'Projects',
            'singular' => 'Project',
        ],
        'purchase_orders' => [
            'plural' => 'Purchase orders',
            'singular' => 'Purchase order',
        ],
        'quotations' => [
            'plural' => 'Quotations',
            'singular' => 'Quotation',
        ],
        'return_orders' => [
            'singular' => 'Customer return',
            'plural' => 'Customer returns',
        ],
        'sales_orders' => [
            'plural' => 'Sales orders',
            'singular' => 'Sales order',
        ],
        'sites' => [
            'singular' => 'Site',
            'plural' => 'Sites',
        ],
        'stock_levels' => [
            'singular' => 'Stock level',
            'plural' => 'Stock levels',
        ],
        'supplier_returns' => [
            'singular' => 'Supplier return',
            'plural' => 'Supplier returns',
        ],
        'tasks' => [
            'singular' => 'Task',
            'plural' => 'Tasks',
        ],
        'tax_codes' => [
            'singular' => 'Tax code',
            'plural' => 'Tax codes',
        ],
        'vat_register_entries' => [
            'singular' => 'VAT register entry',
            'plural' => 'VAT register entries',
        ],
        'vat_settlements' => [
            'singular' => 'VAT settlement',
            'plural' => 'VAT settlements',
        ],
        'warehouses' => [
            'singular' => 'Warehouse',
            'plural' => 'Warehouses',
        ],
    ],
    'fields' => [
        'address_line' => 'Address',
        'amends_sales_order_id' => 'Amends order',
        'city' => 'City',
        'company_id' => 'Company',
        'costing_method' => 'Costing method',
        'country' => 'Country',
        'created_at' => 'Created',
        'credited_invoice_id' => 'Credited invoice',
        'currency' => 'Currency',
        'customer' => 'Customer',
        'description' => 'Description',
        'direction' => 'Direction',
        'einvoice_pec_email' => 'PEC email',
        'einvoice_recipient_code' => 'Recipient code',
        'einvoice_transmission_format' => 'Transmission format',
        'fiscal_country' => 'Fiscal country',
        'invoice_type' => 'Type',
        'is_customer' => 'Customer',
        'is_supplier' => 'Supplier',
        'journal_entry_id' => 'Journal entry',
        'name' => 'Name',
        'notes' => 'Notes',
        'number' => 'Number',
        'opportunity_id' => 'Opportunity',
        'ordered_at' => 'Ordered at',
        'party' => 'Party',
        'payment_term_id' => 'Payment term',
        'postal_code' => 'Postal code',
        'posted_at' => 'Posted at',
        'project_id' => 'Project',
        'province' => 'Province',
        'quotation_id' => 'Quotation',
        'reference' => 'Reference',
        'revises_quotation_id' => 'Revises quotation',
        'sku' => 'SKU',
        'status' => 'Status',
        'supplier' => 'Supplier',
        'tax_id' => 'Tax code',
        'tracing_type' => 'Tracing',
        'uom' => 'Unit of measure',
        'updated_at' => 'Updated',
        'vat_number' => 'VAT number',
        'version' => 'Version',
    ],
    'kpis' => [
        'openOrders' => 'Open orders',
        'revenue' => 'Revenue',
        'shipped' => 'Shipped',
    ],
    'navigation' => [
        'accounting' => 'ERP - Accounting',
        'inventory' => 'ERP - Inventory',
        'master_data' => 'ERP - Master data',
        'purchasing' => 'ERP - Purchasing',
        'sales' => 'ERP - Sales',
        'treasury' => 'ERP - Treasury',
    ],
    'sections' => [
        'address' => 'Address',
        'document' => 'Document',
        'einvoice' => 'Electronic invoicing',
        'identity' => 'Identity',
        'links' => 'Related documents',
        'notes' => 'Notes',
        'order' => 'Order',
        'project' => 'Project',
        'quotation' => 'Quotation',
        'record' => 'Record',
        'stock' => 'Stock and costing',
    ],
];
