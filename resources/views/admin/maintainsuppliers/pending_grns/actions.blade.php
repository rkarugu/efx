@if ($grn->documents_sent)
    @if (!$grn->documents_received && can('process', 'pending-grns'))
        <span class="row-action">
            <form action="{{ route('grns.receive-documents', $grn->grn_number) }}" method="post">
                @csrf
                @method('put')
                <button type="submit" style="border:0; padding:0; background:none; color:#337ab7" data-toggle="tooltip"
                    title="Receive Documents">
                    <i aria-hidden="true" class="fa fa-check-circle" style="font-size: 20px;"></i>
                </button>
            </form>
        </span>
    @elseif(can('process', 'pending-grns'))
        <a class="btn btn-biz-purplish btn-sm" data-toggle="tooltip" title="Process GRN"
            href="{{ route('maintain-suppliers.supplier_invoice_order_details', [
                'order_id' => $grn->order_id,
                'supplier' => $grn->supplier_id,
                'grn' => $grn->grn_number,
            ]) }}">
            <i class="fa fa-file-text"></i>
        </a>
    @endif
@endif
