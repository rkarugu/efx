@if ($logged_user_info->role_id == 1 || isset($my_permissions['genralledger___view']))
                <?php $active_class = isset($model) && in_array($model, [
                'account-inquiry',
                'assign-account-view',
                'transactions-without-branches',
                'transactions-without-account',
                'general-ledger-utility',
                'petty-cash-approvals',
                'genralLedger-bank-deposite',
                'genralLedger-bank-transfer',
                'bank-accounts',
                'genralLedger-cheque',
                'genralLedger-bills',
                'expenses',
                'genralLedger-gl_entries',
                'profit-and-loss',
                'balance-sheet',
                'chart-of-accounts',
                'bank-accounts',
                'trial-balances',
                'journal-entries',
                'maintain-wallet',
                'gl-transaction-report-summary',
                'trading-profit-and-loss',
                'detailed-transaction-summary',
                'edit-ledger',
                'journal-inquiry',
                'general-ledger-reports',
                'petty-cash-requests',
                'petty-cash-requests-initial-approval',
                'petty-cash-requests-final-approval',
                'petty-cash-requests-request-details',
                'petty-cash-requests-processed',
                'petty-cash-requests-failed',
                'petty-cash-requests-rejected',
                'petty-cash-requests-expunged',
                'petty-cash-requests-logs',
                'update-customer-to-gl',
                'gl_reconciliation',
                'gl_reconciliation-overview'
            ]) ? 'active' : ''; ?>
            <li class="treeview <?= $active_class ?>">
                <a href="#"><i class="fa fa-landmark"></i><span>General Ledger</span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['maintain-wallet___view']))
                        <li class="@if (isset($model) && $model == 'maintain-wallet') active @endif"><a
                                    href="{!! route('maintain-wallet.index') !!}"><i class="fa fa-circle"></i>
                                Maintain Wallets
                            </a></li>
                    @endif
                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['chart-of-accounts___view']))
                        <li class="@if (isset($model) && $model == 'chart-of-accounts') active @endif"><a
                                    href="{!! route('chart-of-accounts.index') !!}"><i class="fa fa-circle"></i>
                                Chart Of Accounts
                            </a></li>
                    @endif
                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['account-inquiry___view']))
                        <li class="@if (isset($model) && $model == 'account-inquiry') active @endif"><a
                                    href="{!! route('admin.account-inquiry.index') !!}"><i class="fa fa-circle"></i> Account Inquiry
                            </a></li>
                    @endif
                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['journal-inquiry___view']))
                        <li class="@if (isset($model) && $model == 'journal-inquiry') active @endif"><a
                                    href="{!! route('admin.journal-inquiry.index') !!}"><i class="fa fa-circle"></i> GL Journal
                                Inquiry </a></li>
                    @endif
                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['transfers___view']))
                            <?php $active_class = isset($model) && in_array($model, ['genralLedger-gl_entries']) ? 'active' : ''; ?>
                        <li class="<?= $active_class ?>"><a href="{!! route('general-ledgers.gl-entries') . '?to=' . date('Y-m-d') . '&from=' . date('Y-m-d') !!}"><i
                                        class="fa fa-circle"></i> GL Transactions </a></li>
                    @endif
                    

                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['gl_reconciliation___view']))
                            <?php $active_class = isset($model) && in_array($model, [
                            'gl_reconciliation',
                            'gl_reconciliation-overview',
                            'gl_reconciliation-verification',
                        ]) ? 'active' : ''; ?>
                        <li class="treeview <?= $active_class ?>"><a href="#"><i class="fa fa-circle"></i>
                                <span>GL Reconciliation</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['gl_reconciliation___overview']))
                                    <li class="@if (isset($model) && $model == 'gl_reconciliation-overview') active @endif">
                                        <a href="{!! route('gl-reconciliation.overview') !!}">
                                            <i class="fa fa-circle"></i>
                                            Overview
                                        </a>
                                    </li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['gl_reconciliation___view']))
                                    <li class="@if (isset($model) && $model == 'gl_reconciliation-verification') active @endif">
                                        <a href="{!! route('gl-reconciliation.list') !!}">
                                            <i class="fa fa-circle"></i>
                                            Verification Records
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['petty-cash-requests___view']))
                            <?php $active_class = isset($model) && in_array($model, [
                            'petty-cash-requests',
                            'petty-cash-requests-initial-approval',
                            'petty-cash-requests-final-approval',
                            'petty-cash-requests-request-details',
                            'petty-cash-requests-processed',
                            'petty-cash-requests-failed',
                            'petty-cash-requests-rejected',
                            'petty-cash-requests-expunged',
                            'petty-cash-requests-logs',
                        ]) ? 'active' : ''; ?>
                        <li class="treeview <?= $active_class ?>"><a href="#"><i class="fa fa-circle"></i>
                                <span>Petty Cash</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['petty-cash-requests-request___view']))
                                    <li class="@if (isset($model) && $model == 'petty-cash-requests') active @endif">
                                        <a href="{!! route('petty-cash-request.create') !!}">
                                            <i class="fa fa-circle"></i>
                                            Request
                                        </a>
                                    </li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['petty-cash-requests-initial-approval___view']))
                                    <li class="@if (isset($model) && $model == 'petty-cash-requests-initial-approval') active @endif">
                                        <a href="{!! route('petty-cash-request.initial-approval') !!}">
                                            <i class="fa fa-circle"></i>
                                            Initial Approval
                                        </a>
                                    </li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['petty-cash-requests-final-approval___view']))
                                    <li class="@if (isset($model) && $model == 'petty-cash-requests-final-approval') active @endif">
                                        <a href="{!! route('petty-cash-request.final-approval') !!}">
                                            <i class="fa fa-circle"></i>
                                            Final Approval
                                        </a>
                                    </li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['petty-cash-requests-processed___view']))
                                    <li class="@if (isset($model) && $model == 'petty-cash-requests-processed') active @endif">
                                        <a href="{!! route('petty-cash-request.processed') !!}">
                                            <i class="fa fa-circle"></i>
                                            Processed Requests
                                        </a>
                                    </li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['petty-cash-requests-failed___view']))
                                    <li class="@if (isset($model) && $model == 'petty-cash-requests-failed') active @endif">
                                        <a href="{!! route('petty-cash-request.failed') !!}">
                                            <i class="fa fa-circle"></i>
                                            Failed Requests
                                        </a>
                                    </li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['petty-cash-requests-rejected___view']))
                                    <li class="@if (isset($model) && $model == 'petty-cash-requests-rejected') active @endif">
                                        <a href="{!! route('petty-cash-request.rejected') !!}">
                                            <i class="fa fa-circle"></i>
                                            Rejected Requests
                                        </a>
                                    </li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['petty-cash-requests-expunged___view']))
                                    <li class="@if (isset($model) && $model == 'petty-cash-requests-expunged') active @endif">
                                        <a href="{!! route('petty-cash-request.expunged') !!}">
                                            <i class="fa fa-circle"></i>
                                            Expunged Requests
                                        </a>
                                    </li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['petty-cash-requests-logs___view']))
                                    <li class="@if (isset($model) && $model == 'petty-cash-requests-logs') active @endif">
                                        <a href="{!! route('petty-cash-request.logs') !!}">
                                            <i class="fa fa-circle"></i>
                                            Logs
                                        </a>
                                    </li>
                                @endif

                            </ul>
                        </li>
                    @endif

                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['expenses___view']))
                            <?php $active_class = isset($model) && in_array($model, ['genralLedger-cheque', 'genralLedger-bills', 'expenses']) ? 'active' : ''; ?>
                        <li class="treeview <?= $active_class ?>"><a href="#"><i class="fa fa-circle"></i>
                                <span>Expenses</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['expenses___bill']))
                                        <?php $active_class = isset($model) && in_array($model, ['genralLedger-bills']) ? 'active' : ''; ?>
                                    <li class="{{ $active_class }}"><a href="{{ route('bills.list') }}"><i
                                                    class="fa fa-circle"></i> Bills </a></li>
                                @endif
                                {{-- @if ($logged_user_info->role_id == 1)
                                  <li  class=""><a href="#"><i class="fa fa-circle"></i> Pay Bills </a></li>
                                @endif --}}
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['expenses___expense']))
                                        <?php $active_class = isset($model) && in_array($model, ['expenses']) ? 'active' : ''; ?>
                                    <li class="{{ $active_class }}"><a href="{{ route('expense.list') }}"><i
                                                    class="fa fa-circle"></i> Expense </a>
                                    </li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['expenses___cheque']))
                                        <?php $active_class = isset($model) && in_array($model, ['genralLedger-cheque']) ? 'active' : ''; ?>
                                    <li class="{{ $active_class }}"><a href="{{ route('cheques.list') }}"><i
                                                    class="fa fa-circle"></i> Cheque </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['bank-accounts___view']))
                            <?php $active_class = isset($model) && in_array($model, ['genralLedger-bank-deposite', 'genralLedger-bank-transfer', 'bank-accounts']) ? 'active' : ''; ?>
                        <li class="treeview <?= $active_class ?>"><a href="#"><i class="fa fa-circle"></i>
                                <span>Banking</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['bank-accounts___view']))
                                        <?php $active_class = isset($model) && in_array($model, ['bank-accounts']) ? 'active' : ''; ?>
                                    <li class="<?= $active_class ?>"><a href="{!! route('bank-accounts.index') !!}"><i
                                                    class="fa fa-circle"></i>Bank
                                            Accounts </a></li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['bank-accounts___bankdeposit']))
                                        <?php $active_class = isset($model) && in_array($model, ['genralLedger-bank-deposite']) ? 'active' : ''; ?>
                                    <li class="{{ $active_class }}"><a
                                                href="{{ route('banking.deposite.list') }}"><i
                                                    class="fa fa-circle"></i> Bank
                                            Deposit </a></li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['bank-accounts___banktransfer']))
                                        <?php $active_class = isset($model) && in_array($model, ['genralLedger-bank-transfer']) ? 'active' : ''; ?>

                                    <li class="{{ $active_class }}"><a
                                                href="{{ route('banking.transfer.list') }}"><i
                                                    class="fa fa-circle"></i>
                                            Transfer </a></li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['bank-accounts___reconcile-daily-transaction']))
                                        <?php $active_class = isset($model) && in_array($model, ['reconcile-daily-transaction']) ? 'active' : ''; ?>
                                    <li class="{{ $active_class }}"><a
                                                href="{{ route('banking.reconcile.daily.transactions') }}"><i
                                                    class="fa fa-circle"></i>
                                            Reconcile Daily Transactions </a></li>
                                @endif
                            </ul>
                        </li>
                    @endif
                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['journal-entries___view']))
                            <?php $active_class = isset($model) && in_array($model, ['journal-entries']) ? 'active' : ''; ?>
                        <li class="<?= $active_class ?>"><a href="{!! route('journal-entries.index') !!}"><i
                                        class="fa fa-circle"></i>Journal
                                Entry </a></li>
                    @endif
                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['journal-entries___processed']))
                            <?php $active_class = isset($model) && in_array($model, ['journal-entries']) ? 'active' : ''; ?>
                        <li class="<?= $active_class ?>"><a href="{!! route('journal-entries.processed_index') !!}"><i
                                        class="fa fa-circle"></i>Processed
                                JV </a></li>
                    @endif

                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['edit-ledger___processed']))
                            <?php $active_class = isset($model) && in_array($model, ['edit-ledger']) ? 'active' : ''; ?>
                        <li class="<?= $active_class ?>">
                            <a href="{!! route('edit-ledger.index') !!}"><i class="fa fa-circle"></i> View Ledger </a>
                        </li>
                    @endif



                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['general-ledger-utility___view']))
                            <?php $active_class = isset($model) && in_array($model, ['assign-account-view', 'transactions-without-branches', 'transactions-without-account', 'update-customer-to-gl']) ? 'active' : ''; ?>
                        <li class="treeview <?= $active_class ?>"><a href="#"><i class="fa fa-circle"></i>
                                <span>Utility</span>
                                <span class="pull-right-container">
                                    <i class="fa fa-angle-left pull-right"></i>
                                </span>
                            </a>
                            <ul class="treeview-menu">
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['assign-account-view___view']))
                                        <?php $active_class = isset($model) && in_array($model, ['assign-account-view']) ? 'active' : ''; ?>
                                    <li class="{{ $active_class }}"><a
                                                href="{{ route('assign_account_view.index') }}"><i
                                                    class="fa fa-circle"></i> Assign Account View </a></li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['transaction-without-branch___view']))
                                        <?php $active_class = isset($model) && in_array($model, ['transactions-without-branches']) ? 'active' : ''; ?>
                                    <li class="{{ $active_class }}"><a
                                                href="{{ route('transactions-without-branches.index') }}"><i
                                                    class="fa fa-circle"></i> Transaction Without Branch </a></li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['transaction-without-account___view']))
                                        <?php $active_class = isset($model) && in_array($model, ['transactions-without-account']) ? 'active' : ''; ?>
                                    <li class="{{ $active_class }}"><a
                                                href="{{ route('transactions-without-account.index') }}"><i
                                                    class="fa fa-circle"></i> Transaction Without Account </a></li>
                                @endif
                                @if ($logged_user_info->role_id == 1 || isset($my_permissions['update-customer-to-gl___view']))
                                        <?php $active_class = isset($model) && in_array($model, ['update-customer-to-gl']) ? 'active' : ''; ?>
                                    <li class="{{ $active_class }}"><a
                                                href="{{ route('update-customer-to-gl.index') }}"><i
                                                    class="fa fa-circle"></i> Add Customer to GL </a></li>
                                @endif
                            </ul>
                        </li>
                    @endif



                    @if ($logged_user_info->role_id == 1 || isset($my_permissions['general-ledger-reports___view']))
                        <li class="treeview @if (isset($model) &&
                                ($model == 'general-ledger-reports' ||
                                    $model == 'trial-balances' ||
                                    $model == 'profit-and-loss' ||
                                    $model == 'trading-profit-and-loss' ||
                                    $model == 'gl-transaction-report-summary' ||
                                    $model == 'detailed-transaction-summary' ||
                                    $model == 'balance-sheet')) active @endif">
                        <li class="<?= $active_class ?>">
                            <a href="{!! route('general-ledger-reports.index') !!}"><i class="fa fa-circle"></i> Reports </a>
                        </li>
                    @endif
                   
                </ul>
            </li>
            @endif