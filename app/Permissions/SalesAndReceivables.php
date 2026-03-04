<?php
function salesAndReceivablesPermissionFunction()
{
    $saledAndReceivablesMainPermissions = [
        'salesandreceivables' => [
            'title' => [
                'Sales & Receivables' => [
                    'model' => 'sales-and-receivables',
                    'permissions' => [
                        'view' => 'view'
                    ],
                    'children' => [
                        'Dashboard' => [
                            'model' => 'module-dashboards',
                            'permissions' => [
                                'sales-and-receivables' => 'sales-and-receivables'
                            ],
                        ],
                        'Maintain Customers' => [
                            'model' => 'maintain-customers',
                            'permissions' => [
                                'view' => 'view',
                                'add' => 'add',
                                'edit' => 'edit',
                                'delete' => 'delete',
                                'enter-customer-payment' => 'enter-customer-payment',
                                'print-receipts' => 'print-receipts',
                                'allocate-receipts' => 'allocate-receipts',
                                'settle-from-fraud' => 'settle-from-fraud'
                            ],
                            'children' => [
                                'Customer Center' => [
                                    'model' => 'customer-centre',
                                    'permissions' => [
                                        'view' => 'view',
                                        'add' => 'add',
                                        'edit' => 'edit',
                                        'delete' => 'delete',
                                        'export' => 'export',
                                        'print' => 'print',
                                        'customer-statement' => 'customer-statement',
                                        'route-customers' => 'route-customers'
                                    ]
                                ],
                            ]
                        ],
                        'Route Manager' => [
                            'model' => 'route-manager',
                            'permissions' => [
                                'view' => 'view'
                            ],
                            'children' => [
                                'Route Listing' => [
                                    'model' => 'route-manager',
                                    'permissions' => [
                                        'view' => 'view'
                                    ]
                                ],
                                'Route Customers' => [
                                    'model' => 'route-customers',
                                    'permissions' => [
                                        'view' => 'view',
                                        'add' => 'add',
                                        'edit' => 'edit',
                                        'remove' => 'remove',
                                        'verify' => 'verify',
                                        'approve' => 'approve',
                                    ],
                                    'children' => [
                                        'Overview' => [
                                            'model' => 'route-customers',
                                            'permissions' => [
                                                'overview' => 'overview'
                                            ]
                                        ],
                                        'Listing' => [
                                            'model' => 'route-customers',
                                            'permissions' => [
                                                'listing' => 'listing'
                                            ]
                                        ],
                                        'Onboarding Requests' => [
                                            'model' => 'route-customers',
                                            'permissions' => [
                                                'onboarding-requests' => 'onboarding-requests'
                                            ]
                                        ],
                                        'Approval Requests' => [
                                            'model' => 'route-customers',
                                            'permissions' => [
                                                'approval-requests' => 'approval-requests'
                                            ]
                                        ],
                                        'Duplicate Requests' => [
                                            'model' => 'route-customers',
                                            'permissions' => [
                                                'duplicate-approval-requests' => 'duplicate-approval-requests'
                                            ]
                                        ],
                                        'Field Visits' => [
                                            'model' => 'route-customers',
                                            'permissions' => [
                                                'field-visits' => 'field-visits'
                                            ]
                                        ],
                                        'Geomapping Schedules' => [
                                            'model' => 'route-customers',
                                            'permissions' => [
                                                'geomapping-schedules' => 'geomapping-schedules',
                                                'edit-schedule' => 'edit-schedule',
                                                'shedule-delete' => 'shedule-delete',
                                                'geomapping-summary' => 'geomapping-summary',
                                                'geomapping-comments' => 'geomapping-comments',
                                                'mark-complete' => 'mark-complete',
                                                'HQ-approve' => 'HQ-approve',


                                            ]
                                        ],
                                        'Geomapping Summary' => [
                                            'model' => 'route-customers',
                                            'permissions' => [
                                                'geomapping-summary' => 'geomapping-summary',
                                            ]
                                        ],
                                        'Rejected Customers' => [
                                            'model' => 'route-customers',
                                            'permissions' => [
                                                'rejected-customers' => 'rejected-customers',


                                            ]
                                        ],
                                    ]
                                ],
                                'Group Representatives' => [
                                    'model' => 'route-group-rep',
                                    'permissions' => [
                                        'view' => 'view',
                                        'add' => 'add',
                                        'reassign' => 'reassign'
                                    ]
                                ],
                            ]
                        ],
                        'Reconciliation' => [
                            'model' => 'reconciliation',
                            'permissions' => [
                                'view' => 'view'
                            ],
                            'children' => [
                                'Overview' => [
                                    'model' => 'reconciliation',
                                    'permissions' => [
                                        'see-overview' => 'see-overview',
                                        'see-overview-route' => 'see-overview-route',
                                        'see-overview-pos' => 'see-overview-pos',
                                        'view-all-branches' => 'view-all-branches',
                                        'run-verification' => 'run-verification',
                                        'complete-verification' => 'complete-verification',
                                        'close-banking' => 'close-banking',
                                        'download-summary' => 'download-summary',
                                        'allocate-cdm' => 'allocate-cdm',
                                        'allocate-cash-banking' => 'allocate-cash-banking',
                                        'add-short-banking-comments' => 'add-short-banking-comments',
                                        'edit-short-banking-comments' => 'edit-short-banking-comments',
                                        'resolve-short-banking-comments' => 'resolve-short-banking-comments',
                                        'gl-reconciliation' => 'gl-reconciliation'
                                    ]
                                ],
                                'Payment Verification' => [
                                    'model' => 'reconciliation',
                                    'permissions' => [
                                        'verification' => 'verification'
                                    ]
                                ],
                                'Payment Approval' => [
                                    'model' => 'reconciliation',
                                    'permissions' => [
                                        'approval' => 'approval'
                                    ]
                                ],
                                'Manual Uploads' => [
                                    'model' => 'reconciliation',
                                    'permissions' => [
                                        'upload' => 'upload'
                                    ]
                                ],
                                'Suspended Transactions' => [
                                    'model' => 'reconciliation',
                                    'permissions' => [
                                        'suspend' => 'suspend'
                                    ]
                                ],
                                'Expunged Transactions' => [
                                    'model' => 'reconciliation',
                                    'permissions' => [
                                        'view' => 'view'
                                    ]
                                ],
                                'Restored Transactions' => [
                                    'model' => 'reconciliation',
                                    'permissions' => [
                                        'view' => 'view'
                                    ]
                                ],
                                'Merged Payments' => [
                                    'model' => 'reconciliation',
                                    'permissions' => [
                                        'view' => 'view',
                                        'reverse-transaction' => 'reverse-transaction'
                                    ]
                                ],
                                'Debtor Transactions' => [
                                    'model' => 'reconciliation',
                                    'permissions' => [
                                        'view-debtor-trans' => 'view-debtor-trans',
                                        'debtor-trans' => 'debtor-trans',
                                        'edit-debtor-reference' => 'edit-debtor-reference',
                                        'update-mispost-channel' => 'update-mispost-channel',
                                    ]
                                ],
                                'Bank Statement Upload' => [
                                    'model' => 'reconciliation',
                                    'permissions' => [
                                        'bank-statement-upload' => 'bank-statement-upload',
                                        // 'bank-statement-HQ' => 'bank-statement-HQ',
                                        'bank-statement-main-account' => 'bank-statement-main-account',
                                        'bank-statement-allocate' => 'bank-statement-allocate',
                                        'bank-statement-allocate-all' => 'bank-statement-allocate-all',
                                        'bank-statement-topup' => 'bank-statement-topup',
                                        'edit-channel' => 'edit-channel',
                                        'bank-statement-allocate-status' => 'bank-statement-allocate-status',
                                        'bank-statement-topup-debit' => 'bank-statement-topup-debit',
                                        'mpesa-statement-topup-debit' => 'mpesa-statement-topup-debit',
                                        'flag-bank-error' => 'flag-bank-error',
                                        'allocate-stock-debtor' => 'allocate-stock-debtor'
                                    ]
                                ],
                                'Manual Upload' => [
                                    'model' => 'reconciliation',
                                    'permissions' => [
                                        'view-manual-upload' => 'view-manual-upload',
                                        'approve-manual-upload' => 'approve-manual-upload',
                                    ]
                                ],
                                'Bank Posting Log' => [
                                    'model' => 'reconciliation',
                                    'permissions' => [
                                        'bank_post_log' => 'bank_post_log'
                                    ]
                                ],
                                'Bank Error Logs' => [
                                    'model' => 'reconciliation',
                                    'permissions' => [
                                        'bank-error-logs' => 'bank-error-logs'
                                    ]
                                ],
                                'Transaction History' => [
                                    'model' => 'reconciliation',
                                    'permissions' => [
                                        'transaction-history' => 'transaction-history'
                                    ]
                                ],
                            ]
                        ],
                        'Cash Sales' => [
                            'model' => 'pos-cash-sales-new',
                            'permissions' => [
                                'view' => 'view',
                                'reserve-transaction' => 'reserve-transaction'
                            ],
                            'children' => [
                                'POS Cash Sales' => [
                                    'model' => 'pos-cash-sales',
                                    'permissions' => [
                                        'view' => 'view',
                                        'view-all' => 'view-all',
                                        'add' => 'add',
                                        'edit' => 'edit',
                                        'super-edit' => 'super-edit',
                                        'show' => 'show',
                                        'edit-values' => 'edit-values',
                                        'return' => 'return',
                                        'return-list' => 'return-list',
                                        'return-list_late' => 'return-list_late',
                                        'print' => 'print',
                                        'pdf' => 'pdf',
                                        're-print' => 're-print',
                                        'resign-esd' => 'resign-esd',
                                        'save' => 'save',
                                        'process' => 'process',
                                        'dispatch' => 'dispatch',
                                        'dispatch-progress' => 'dispatch-progress',
                                        'archive' => 'archive',
                                        'show-total' => 'show-total',
                                        'dispatch-slip' => 'dispatch-slip',
                                        'delayed-orders' => 'delayed-orders',
                                        'archived-orders' => 'archived-orders',
                                        'process-bank-overpayment' => 'process-bank-overpayment',
                                        'promotions_discounts' => 'promotions_discounts',
                                        'show_all_return' => 'show_all_return',
                                        'consume_expired_transactions' => 'consume_expired_transactions',
                                        'drop_cash'=>'drop_cash'
                                    ]
                                ],
                                'POS Supermarket' => [
                                    'model' => 'pos-supermarket',
                                    'permissions' => [
                                        'view' => 'view',
                                        'add' => 'add',
                                        'print' => 'print',
                                    ]
                                ],
                                'Cashier management' => [
                                    'model' => 'cashier-management',
                                    'permissions' => [
                                        'view' => 'view',
                                        'view_all' => 'view_all',
                                        'cash-banking-report' => 'cash-banking-report',
                                        // 'transaction' => 'transactions',
                                        // 'pos-payments-consumption' => 'pos-payments-consumption',
                                    ],
                                    'children' => [
                                        'All Cashiers' => [
                                            'model' => 'cashier-management',
                                            'permissions' => [
                                                'view' => 'view',
                                            ],
                                        ],
                                        'Drop Transactions' => [
                                            'model' => 'cashier-management',
                                            'permissions' => [
                                                'transactions' => 'transactions',
                                            ],
                                        ],
                                        'Payment Allocations' => [
                                            'model' => 'cashier-management',
                                            'permissions' => [
                                                'pos-payments-consumption' => 'pos-payments-consumption',
                                            ],
                                        ]
                                    ]
                                ],
                                'POS Cash Payments' => [
                                    'model' => 'pos_cash_payments',
                                    'permissions' => [
                                        'view' => 'view',
                                        'initiate' => 'initiate',
                                        'approve' => 'approve',
                                    ],
                                  
                                ],
                                'Tender  Entry' => [
                                    'model' => 'tender-entry',
                                    'permissions' => [
                                        'view' => 'view',
                                    ],
                                    'children' => [
                                        'Channels summery' => [
                                            'model' => 'tender-entry',
                                            'permissions' => [
                                                'transaction' => 'transactions',
                                            ]
                                        ],
                                        'Transaction by channel' => [
                                            'model' => 'tender-entry',
                                            'permissions' => [
                                                'summery' => 'summery',
                                            ]
                                        ],
                                    ]
                                ],
                                //                                'tender-entry' => [
                                //                                    'view'=>'view',
                                //                                    'transaction'=>'transactions',
                                //                                    'summery'=>'summery',
                                //                                ],
                                'POS Cash Sales - II' => [
                                    'model' => 'pos-cash-sales-new',
                                    'permissions' => [
                                        'view' => 'view',
                                        'add' => 'add',
                                        'edit' => 'edit',
                                        'show' => 'show',
                                        'edit-values' => 'edit-values',
                                        'return' => 'return',
                                        'return-list' => 'return-list',
                                        'print' => 'print',
                                        'pdf' => 'pdf'
                                    ]
                                ],
                                'Petty Cash' => [
                                    'model' => 'petty-cash',
                                    'permissions' => [
                                        'view' => 'view'
                                    ]
                                ],
                                'Cash Sales Return' => [
                                    'model' => 'pos-return-list',
                                    'permissions' => [
                                        'return-list' => 'return-list',
                                        'return-accept' => 'return-accept',
                                    ]
                                ],
                                'POS Cash Sales R' => [
                                    'model' => 'pos-cash-sales-r',
                                    'permissions' => [
                                        'view' => 'view',
                                        'add' => 'add',
                                        'edit' => 'edit',
                                        'show' => 'show',
                                        'edit-values' => 'edit-values',
                                        'return' => 'return',
                                        'return-list' => 'return-list',
                                        'print' => 'print',
                                        'pdf' => 'pdf',
                                        're-print' => 're-print'
                                    ]
                                ],
                            ]
                        ],
                        'Credit Sales' => [
                            'model' => 'sales-invoice',
                            'permissions' => [
                                'invoices' => 'invoices',
                                'add' => 'add',
                                'invoices-create' => 'invoices-create',
                                'cheques' => 'cheques',
                                'view-invoice' => 'view-invoice',
                                'edit-invoice' => 'edit-invoice',
                                'edit-values' => 'edit-values',
                                'route-customer' => 'route-customer',
                                'confirm-invoice' => 'confirm-invoice',
                                'confirm-invoice-r' => 'confirm-invoice-r'

                            ],
                            'children' => [
                                'Cheque Management' => [
                                    'model' => 'cheque-management',
                                    'permissions' => [
                                        'view' => 'view',
                                        'add' => 'add',
                                        'edit' => 'edit',
                                        'delete' => 'delete',
                                        'update-status' => 'update-status',
                                        'deposit-cheque' => 'deposit-cheque',
                                    ]
                                ],
                            ]

                        ],

                        'Salesman Invoice' => [
                            'model' => 'sales-invoice',
                            'permissions' => [
                                'view' => 'view',
                                // 'add' => 'add',
                                // 'edit' => 'edit',
                                // 'edit-values' => 'edit-values',
                                // 'route-customer' => 'route-customer',
                                // 'confirm-invoice' => 'confirm-invoice',
                                // 'confirm-invoice-r' => 'confirm-invoice-r'
                            ],
                            'children' => [
                                'Print Invoice/Delivery Note' => [
                                    'model' => 'print-invoice-delivery-note',
                                    'permissions' => [
                                        'view' => 'view',
                                        'print' => 'print',
                                        'pdf' => 'pdf'
                                    ]
                                ],

                            ]
                        ],
                        'Salesman Returns' => [
                            'model' => 'sales-invoice',
                            'permissions' => [
                                'view' => 'view',
                                'add' => 'add',
                                'edit' => 'edit',
                                'edit-values' => 'edit-values',
                                'route-customer' => 'route-customer',
                                'confirm-invoice' => 'confirm-invoice',
                                'confirm-invoice-r' => 'confirm-invoice-r'
                            ],
                            'children' => [
                                // 'Print Invoice/Delivery Note' => [
                                //     'model' => 'print-invoice-delivery-note',
                                //     'permissions' => [
                                //         'view' => 'view',
                                //         'print' => 'print',
                                //         'pdf' => 'pdf'
                                //     ]
                                // ],
                                'Sales Invoice Returns' => [
                                    'model' => 'print-invoice-delivery-note',
                                    'permissions' => [
                                        'return' => 'return'
                                    ]
                                ],
                                'Over limit Returns' => [
                                    'model' => 'approver-limit-returns',
                                    'permissions' => [
                                        'view' => 'view'
                                    ],
                                    'children' => [
                                        'Approver 1' => [
                                            'model' => 'approver-limit-returns',
                                            'permissions' => [
                                                'approver-1' => 'approver-1'
                                            ]
                                        ],
                                        'Approver 2' => [
                                            'model' => 'approver-limit-returns',
                                            'permissions' => [
                                                'approver-2' => 'approver-2'
                                            ]
                                        ],
                                        'Late Returns' => [
                                            'model' => 'approver-limit-returns',
                                            'permissions' => [
                                                'late-returns' => 'late-returns'
                                            ]
                                        ],
                                        'Over Limit Returns' => [
                                            'model' => 'approver-limit-returns',
                                            'permissions' => [
                                                'return-confirm-report' => 'return-confirm-report'
                                            ]
                                        ],
                                    ]
                                ],
                                'Processed Returns' => [
                                    'model' => 'processed-returns',
                                    'permissions' => [
                                        'view' => 'view'
                                    ]
                                ],
                                'Rejected Returns' => [
                                    'model' => 'rejected-returns',
                                    'permissions' => [
                                        'view' => 'view'
                                    ]
                                ],
                                'Abnormal Returns' => [
                                    'model' => 'abnormal-returns',
                                    'permissions' => [
                                        'view' => 'view'
                                    ]
                                ],
                                'Completed Returns' => [
                                    'model' => 'completed-returns',
                                    'permissions' => [
                                        'view' => 'view'
                                    ]
                                ],
                                'Detailed Completed Returns' => [
                                    'model' => 'detailed-completed-returns',
                                    'permissions' => [
                                        'view' => 'view'
                                    ]
                                ],
                            ]
                        ],
                        'Order Taking' => [
                            'model' => 'order-taking',
                            'permissions' => [
                                'view' => 'view'
                            ],
                            'children' => [
                                'Salesman Orders' => [
                                    'model' => 'salesman-orders',
                                    'permissions' => [
                                        'view' => 'view'
                                    ]
                                ],
                                'Salesman Shifts' => [
                                    'model' => 'order-taking-schedules',
                                    'permissions' => [
                                        'view' => 'view',
                                        'reopen-requests' => 'reopen-requests',
                                    ]
                                ],
                                'Offsite Shift Requests' => [
                                    'model' => 'order-taking-schedules',
                                    'permissions' => [
                                        'offsite-requests' => 'offsite-requests'
                                    ]
                                ],
                                'Reported Issues' => [
                                    'model' => 'reported-shift-issues',
                                    'permissions' => [
                                        'view' => 'view',
                                        'resolve-salesman-reported-issues' => 'resolve-salesman-reported-issues',
                                        'hq-comment' => 'hq-comment'
                                    ]
                                ],
                                'Salesman Shift' => [
                                    'model' => 'salesman-shift',
                                    'permissions' => [
                                        'view' => 'view',
                                        'reopen-from-backend' => 'reopen-from-backend'
                                    ]
                                ],
                                'Sales Commission Bands' => [
                                    'model' => 'sales-commission-bands',
                                    'permissions' => [
                                        'view' => 'view'
                                    ]
                                ]

                            ]

                        ],
                        'Dispatch & Delivery' => [
                            'model' => 'dispatch-and-delivery',
                            'permissions' => [
                                'view' => 'view'
                            ],
                            'children' => [
                                'Dispatch Loading Sheet' => [
                                    'model' => 'store-loading-sheet',
                                    'permissions' => [
                                        'view' => 'view',
                                        'view-undispatched' => 'view-undispatched'
                                    ]
                                ],
                                'Dispatched Loading Sheets' => [
                                    'model' => 'dispatched-loading-sheets',
                                    'permissions' => [
                                        'view' => 'view',
                                        'view-all' => 'view-all',
                                        'offsite-requests' => 'offsite-requests'
                                    ]
                                ],
                                'Deliveries' => [
                                    'model' => 'delivery-schedule',
                                    'permissions' => [
                                        'view' => 'view',
                                        'issue-gate-pass' => 'issue-gate-pass',
                                        'end-schedule' => 'end-schedule',
                                        'assign-vehicles' => 'assign-vehicles',
                                    ]
                                ],
                                'Shift Delivery Report' => [
                                    'model' => 'shift_delivery_report',
                                    'permissions' => [
                                        'view' => 'view'
                                    ]
                                ],
                                'POS Dispatch' => [
                                    'model' => 'dispatch',
                                    'permissions' => [
                                        'view' => 'view'
                                    ]
                                ],
                                'Credit Sales Dispatch' => [
                                    'model' => 'credit-sales',
                                    'permissions' => [
                                        'dispatch' => 'dispatch'
                                    ]
                                ],
                                'POS cash sales Returns' => [
                                    'model' => 'pos-return-list',
                                    'permissions' => [
                                        'return-accept' => 'return-accept',
                                        'return-list_late' => 'return-list_late'
                                    ]
                                ],
                            ]
                        ],
                        'Utilities' => [
                            'model' => 'sales-and-receivables-reports',
                            'permissions' => [
                                'view' => 'view'
                            ],
                            'children' => [
                                'End of Day' => [
                                    'model' => 'end-of-day-utility',
                                    'permissions' => [
                                        'view' => 'view',
                                        'view-all-branches' => 'view-all-branches',
                                        'run-routine' => 'run-routine',
                                        'close' => 'close',
                                        'detailed' => 'detailed',
                                    ]
                                ],
                                'Missing Invoice Numbers' => [
                                    'model' => 'number-series-utility',
                                    'permissions' => [
                                        'missing_invoice_series_numbers' => 'missing_invoice_series_numbers',
                                    ]
                                ],
                                'Split Routes' => [
                                    'model' => 'route-split',
                                    'permissions' => [
                                        'view' => 'view',
                                        'add' => 'add'
                                    ]
                                ],
                                'Transaction Mispost' => [
                                    'model' => 'transaction-mispost',
                                    'permissions' => [
                                        'view' => 'view',
                                        'add' => 'add',
                                    ]
                                ],
                                'Bank Statement Mispost' => [
                                    'model' => 'bank-statement-mispost',
                                    'permissions' => [
                                        'view' => 'view',
                                        'add' => 'add',
                                    ]
                                ],
                            ],
                        ],

                        'Petty Cash Approvals' => [
                            'model' => 'petty-cash-approvals',
                            'permissions' => [
                                'view' => 'view',
                            ],
                            'children' => [
                                'Approvals' => [
                                    'model' => 'petty-cash-approvals',
                                    'permissions' => [
                                        'initial_approval' => 'initial_approval',
                                        'final_approval' => 'final_approval',
                                        'successful_allocations' => 'successful_allocations',
                                        'export_successful_allocations' => 'export_successful_allocations',
                                        'failed_deposits' => 'failed_deposits',
                                        'rejected_deposits' => 'rejected_deposits',
                                        'expunged_deposits' => 'expunged_deposits',
                                        'logs' => 'logs'
                                    ]
                                ],
                                'Undisbursed Petty Cash' => [
                                    'model' => 'undisbursed-petty-cash',
                                    'permissions' => [
                                        'view' => 'view',
                                        'approve' => 'approve',
                                        'reject' => 'reject',
                                    ],
                                ],
                            ]

                        ],
                        'Reports' => [
                            'model' => 'sales-and-receivables-reports',
                            'permissions' => [
                                'view' => 'view'
                            ],
                            'children' => [
                                'Sales Summary Report' => [
                                    'model' => 'summary-report',
                                    'permissions' => ['sales_summary' => 'sales_summary']
                                ],
                                'Sales of Product by Date Report' => [
                                    'model' => 'summary-report',
                                    'permissions' => ['detailed_sales_report' => 'detailed_sales_report']
                                ],
                                'Sales by Date Report' => [
                                    'model' => 'summary-report',
                                    'permissions' => ['sales_by_date_report' => 'sales_by_date_report']
                                ],
                                'Daily Sales Report' => [
                                    'model' => 'route-reports',
                                    'permissions' => ['weekly-sales-report' => 'weekly-sales-report']
                                ],
                                'Promotion Sales Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => ['promotion-sales' => 'promotion-sales']
                                ],
                                'Discount Sales Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => ['discount-sales' => 'discount-sales']
                                ],
                                'Sales Per Supplier Per Route Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => ['sales-per-supplier-per-route' => 'sales-per-supplier-per-route']
                                ],
                                'Sales Analysis Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => ['sales-analysis' => 'sales-analysis']
                                ],
                                'Daily Sales Margin Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => ['daily-sales-margin' => 'daily-sales-margin']
                                ],
                                'Shift Summary Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => ['shift-summary' => 'shift-summary']
                                ],
                                'Salesman Trip Summary Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => ['salesman-trip-summary' => 'salesman-trip-summary']
                                ],
                                'Route Profitability Report' => [
                                    'model' => 'route-profitibility-report',
                                    'permissions' => ['view' => 'view']
                                ],
                                'Dispatch Items Report' => [
                                    'model' => 'dispatch-pos-invoice-sales',
                                    'permissions' => ['dispatch-report' => 'dispatch-report']
                                ],
                                'Inventory Valuation Report' => [
                                    'model' => 'summary-report',
                                    'permissions' => ['inventory_sales_report' => 'inventory_sales_report']
                                ],
                                'Customer Aging Analysis Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => ['customer-aging-analysis' => 'customer-aging-analysis']
                                ],
                                'Customer Statement Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => ['customer-statement' => 'customer-statement']
                                ],
                                'Loading Schedule vs Stocks Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => ['loading-schedule-vs-stock-report' => 'loading-schedule-vs-stock-report']
                                ],
                                'Delivery Schedule Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => ['delivery-schedule-report' => 'delivery-schedule-report']
                                ],
                                'Customer Balances Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => ['customer-balances-report' => 'customer-balances-report']
                                ],
                                'Route Performance Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => ['route-performance-report' => 'route-performance-report']
                                ],
                                'Group Performance Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => ['group-performance-report' => 'group-performance-report']
                                ],
                                'Customer Invoices Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => ['customer_invoices' => 'customer_invoices']
                                ],
                                'Unsigned Invoices Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => ['customer_invoices' => 'customer_invoices']
                                ],
                                'Daily Cash Receipt Summary Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => ['daily-cash-receipt-summary' => 'daily-cash-receipt-summary']
                                ],
                                'Vat Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => ['vat-report' => 'vat-report']
                                ],
                                'ESD Vat Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => ['vat-report' => 'vat-report']
                                ],
                                'Till Direct Banking Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => ['till-direct-banking-report' => 'till-direct-banking-report']
                                ],
                                'Sales Vs Stocks Ledger Report' => [
                                    'model' => '',
                                    'permissions' => []
                                ],
                                'Gross Profit Summary Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => ['gross-profit' => 'gross-profit']
                                ],
                                'EOD Detailed Report' => [
                                    'model' => 'summary-report',
                                    'permissions' => ['detailed' => 'detailed']
                                ],
                                'EOD Summary Report' => [
                                    'model' => 'summary-report',
                                    'permissions' => ['summary' => 'summary']
                                ],
                                'Dashboard Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => ['dashboard-report' => 'dashboard-report']
                                ],
                                'Onsite Vs Offsite Shift Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => [
                                        'onsite-vs-offsite-shifts-report' => 'onsite-vs-offsite-shifts-report',
                                    ]
                                ],
                                'SalesMan shift Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => [
                                        'salesman_shift_report' => 'salesman_shift_report'
                                    ]
                                ],
                                'Operation Shift' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => [
                                        'operation-shift' => 'operation-shift'
                                    ]
                                ],
                                'Missing Items Sales' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => [
                                        'missing-items-sales' => 'missing-items-sales'
                                    ]
                                ],
                                'POS Overview Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => [
                                        'pos-overview-report' => 'pos-overview-report'
                                    ]
                                ],
                                'Reported Missing Items' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => [
                                        'reported-missing-items' => 'reported-missing-items'
                                    ]
                                ],
                                'New Items' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => [
                                        'reported-new-items' => 'reported-new-items'
                                    ]
                                ],
                                'Price Conflicts' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => [
                                        'reported-price-conflicts' => 'reported-price-conflicts'
                                    ]
                                ],
                                'Salesman Performance Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => [
                                        'salesman-performance-report' => 'salesman-performance-report'
                                    ]
                                ],
                                'Driver Performance Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => [
                                        'driver-performance-report' => 'driver-performance-report'
                                    ]
                                ],
                                'Competing Brands' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => [
                                        'competing-brands-reports' => 'competing-brands-reports'
                                    ]
                                ],
                                'EOD Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => [
                                        'eod-report' => 'eod-report'
                                    ]
                                ],
                                'Unbalanced Invoices' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => [
                                        'unbalanced-invoices' => 'unbalanced-invoices',
                                        'reprocess-invoices' => 'reprocess-invoices'
                                    ]
                                ],
                                'Debtors Report' => [
                                    'model' => 'sales-and-receivables-reports',
                                    'permissions' => [
                                        'debtors-report' => 'debtors-report',
                                    ]
                                ],


                            ],
                        ],

                    ],
                ]
            ]
        ]
    ];

    return $saledAndReceivablesMainPermissions;
}


function flattenSalesAndReceivablesPermissions($permissions, &$flattenedPermissions = [], $parentModel = '')
{
    foreach ($permissions as $key => $permission) {
        if (isset($permission['permissions'])) {
            foreach ($permission['permissions'] as $permissionKey => $permissionValue) {
                $modelPermissionKey = strtolower(($parentModel ? $parentModel . '___' : '') . $permission['model'] . '___' . $permissionKey);
                $flattenedPermissions[$modelPermissionKey] = $permissionValue;
            }
        }
        if (isset($permission['children'])) {
            flattenSalesAndReceivablesPermissions($permission['children'], $flattenedPermissions, $permission['model']);
        }
    }
}
