<?php

declare(strict_types=1);

return [
    'actions' => [
        'newSalesOrder' => 'Nuevo pedido de venta',
    ],
    'entities' => [
        'accounts' => [
            'singular' => 'Cuenta',
            'plural' => 'Plan de cuentas',
        ],
        'bank_accounts' => [
            'singular' => 'Cuenta bancaria',
            'plural' => 'Cuentas bancarias',
        ],
        'bank_statements' => [
            'singular' => 'Extracto bancario',
            'plural' => 'Extractos bancarios',
        ],
        'companies' => [
            'singular' => 'Empresa',
            'plural' => 'Empresas',
        ],
        'contacts' => [
            'singular' => 'Contacto',
            'plural' => 'Contactos',
        ],
        'customers' => [
            'plural' => 'Clientes',
            'singular' => 'Cliente',
        ],
        'delivery_notes' => [
            'singular' => 'Albarán',
            'plural' => 'Albaranes',
        ],
        'document_sequences' => [
            'singular' => 'Serie de documentos',
            'plural' => 'Series de documentos',
        ],
        'fiscal_periods' => [
            'singular' => 'Periodo fiscal',
            'plural' => 'Periodos fiscales',
        ],
        'fiscal_years' => [
            'singular' => 'Ejercicio fiscal',
            'plural' => 'Ejercicios fiscales',
        ],
        'goods_receipts' => [
            'singular' => 'Recepción de mercancía',
            'plural' => 'Recepciones de mercancía',
        ],
        'invoices' => [
            'plural' => 'Facturas',
            'singular' => 'Factura',
        ],
        'items' => [
            'plural' => 'Artículos',
            'singular' => 'Artículo',
        ],
        'journal_entries' => [
            'singular' => 'Asiento contable',
            'plural' => 'Asientos contables',
        ],
        'leads' => [
            'singular' => 'Lead',
            'plural' => 'Leads',
        ],
        'movements' => [
            'singular' => 'Movimiento',
            'plural' => 'Movimientos',
        ],
        'opportunities' => [
            'singular' => 'Oportunidad',
            'plural' => 'Oportunidades',
        ],
        'parties' => [
            'plural' => 'Interlocutores',
            'singular' => 'Interlocutor',
        ],
        'partner_pools' => [
            'singular' => 'Grupo de socios',
            'plural' => 'Grupos de socios',
        ],
        'payment_requests' => [
            'singular' => 'Solicitud de pago',
            'plural' => 'Solicitudes de pago',
        ],
        'payment_runs' => [
            'singular' => 'Remesa de pagos',
            'plural' => 'Remesas de pagos',
        ],
        'payment_terms' => [
            'singular' => 'Condición de pago',
            'plural' => 'Condiciones de pago',
        ],
        'payments' => [
            'singular' => 'Pago',
            'plural' => 'Pagos',
        ],
        'price_lists' => [
            'singular' => 'Tarifa',
            'plural' => 'Tarifas',
        ],
        'projects' => [
            'plural' => 'Proyectos',
            'singular' => 'Proyecto',
        ],
        'purchase_orders' => [
            'plural' => 'Pedidos de compra',
            'singular' => 'Pedido de compra',
        ],
        'quotations' => [
            'plural' => 'Presupuestos',
            'singular' => 'Presupuesto',
        ],
        'return_orders' => [
            'singular' => 'Devolución de cliente',
            'plural' => 'Devoluciones de clientes',
        ],
        'sales_orders' => [
            'plural' => 'Pedidos de venta',
            'singular' => 'Pedido de venta',
        ],
        'sites' => [
            'singular' => 'Sede',
            'plural' => 'Sedes',
        ],
        'stock_levels' => [
            'singular' => 'Existencia',
            'plural' => 'Existencias',
        ],
        'supplier_returns' => [
            'singular' => 'Devolución a proveedor',
            'plural' => 'Devoluciones a proveedores',
        ],
        'tasks' => [
            'singular' => 'Tarea',
            'plural' => 'Tareas',
        ],
        'tax_codes' => [
            'singular' => 'Código de impuesto',
            'plural' => 'Códigos de impuesto',
        ],
        'vat_register_entries' => [
            'singular' => 'Asiento de IVA',
            'plural' => 'Asientos de IVA',
        ],
        'vat_settlements' => [
            'singular' => 'Liquidación de IVA',
            'plural' => 'Liquidaciones de IVA',
        ],
        'warehouses' => [
            'singular' => 'Almacén',
            'plural' => 'Almacenes',
        ],
    ],
    'fields' => [
        'address_line' => 'Dirección',
        'amends_sales_order_id' => 'Modifica el pedido',
        'city' => 'Ciudad',
        'company_id' => 'Sociedad',
        'costing_method' => 'Método de costeo',
        'country' => 'País',
        'created_at' => 'Creado',
        'credited_invoice_id' => 'Factura abonada',
        'currency' => 'Moneda',
        'customer' => 'Cliente',
        'description' => 'Descripción',
        'direction' => 'Dirección',
        'einvoice_pec_email' => 'Correo PEC',
        'einvoice_recipient_code' => 'Código de destinatario',
        'einvoice_transmission_format' => 'Formato de transmisión',
        'fiscal_country' => 'País fiscal',
        'invoice_type' => 'Tipo',
        'is_customer' => 'Cliente',
        'is_supplier' => 'Proveedor',
        'journal_entry_id' => 'Asiento contable',
        'name' => 'Nombre',
        'notes' => 'Notas',
        'number' => 'Número',
        'opportunity_id' => 'Oportunidad',
        'ordered_at' => 'Pedido el',
        'party' => 'Interlocutor',
        'payment_term_id' => 'Condición de pago',
        'postal_code' => 'Código postal',
        'posted_at' => 'Contabilizado el',
        'project_id' => 'Proyecto',
        'province' => 'Provincia',
        'quotation_id' => 'Presupuesto',
        'reference' => 'Referencia',
        'revises_quotation_id' => 'Revisa el presupuesto',
        'sku' => 'SKU',
        'status' => 'Estado',
        'supplier' => 'Proveedor',
        'tax_id' => 'Código fiscal',
        'tracing_type' => 'Trazabilidad',
        'uom' => 'Unidad de medida',
        'updated_at' => 'Actualizado',
        'vat_number' => 'NIF-IVA',
        'version' => 'Versión',
    ],
    'kpis' => [
        'openOrders' => 'Pedidos abiertos',
        'revenue' => 'Ingresos',
        'shipped' => 'Enviados',
    ],
    'navigation' => [
        'accounting' => 'ERP - Contabilidad',
        'inventory' => 'ERP - Inventario',
        'master_data' => 'ERP - Datos maestros',
        'purchasing' => 'ERP - Compras',
        'sales' => 'ERP - Ventas',
        'treasury' => 'ERP - Tesorería',
    ],
    'sections' => [
        'address' => 'Dirección',
        'document' => 'Documento',
        'einvoice' => 'Facturación electrónica',
        'identity' => 'Identidad',
        'links' => 'Documentos relacionados',
        'notes' => 'Notas',
        'order' => 'Pedido',
        'project' => 'Proyecto',
        'quotation' => 'Presupuesto',
        'record' => 'Registro',
        'stock' => 'Existencias y costeo',
    ],
];
