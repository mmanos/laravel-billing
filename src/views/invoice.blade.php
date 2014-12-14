<style>
	.table th {
		vertical-align: bottom;
		font-weight: bold;
		padding: 8px;
		line-height: 20px;
		text-align: left;
	}
	.table td {
		padding: 8px;
		line-height: 20px;
		text-align: left;
		vertical-align: top;
		border-top: 1px solid #dddddd;
	}
</style>

<!-- Invoice Info -->
<p>
	@if (isset($product))
		<strong>Product:</strong> {{ $product }}<br>
	@endif
	<strong>Invoice ID:</strong> {{ $invoice->id }}<br>
	<strong>Invoice Date:</strong> {{ date('M jS, Y', strtotime($invoice->date)) }}<br>
</p>

<!-- Extra / VAT Information -->
@if (isset($vat))
	<p>{{ $vat }}</p>
@endif

<br><br>

<!-- Invoice Table -->
<table width="100%" class="table" border="0">
	<tr>
		<th align="left">Description</th>
		<th align="right">Date</th>
		<th align="right">Amount</th>
	</tr>
	
	<!-- Display The Invoice Items -->
	@foreach ($invoice->items() as $item)
		<tr>
			@if ($item->subscription_id)
				<td>
					@if ($item->description)
						{{ $item->description }}
					@else
						Subscription
						
						@if ($item->subscription())
							to {{ ucwords(str_replace(array('_', '-'), ' ', $item->subscription()->plan)) }}
						@endif
						
						@if ($item->quantity > 1)
							(x{{ $item->quantity }})
						@endif
					@endif
				</td>
				<td>
					@if ($item->period_start && $item->period_end)
						{{ date('M jS, Y', strtotime($item->period_start)) }}
						-
						{{ date('M jS, Y', strtotime($item->period_end)) }}
					@endif
				</td>
			@else
				<td>{{ $item->description }}</td>
				<td>&nbsp;</td>
			@endif
			
			@if ($item->amount >= 0)
				<td>${{ number_format($item->amount / 100, 2) }}</td>
			@else
				<td>-${{ number_format(abs($item->amount) / 100, 2) }}</td>
			@endif
		</tr>
	@endforeach
	
	<!-- Display The Subtotal -->
	@if ($invoice->subtotal)
		<tr>
			<td>&nbsp;</td>
			<td style="text-align: right;">Subtotal:</td>
			<td><strong>${{ number_format($invoice->subtotal / 100, 2) }}</strong></td>
		</tr>
	@endif
	
	<!-- Display Any Discounts -->
	@if ($invoice->discounts)
		@foreach ($invoice->discounts as $discount)
			<tr>
				<td>
					{{ array_get($discount, 'coupon') }}
					
					@if (array_get($discount, 'amount_off'))
						(${{ array_get($discount, 'amount_off') / 100 }} Off)
					@else
						({{ array_get($discount, 'percent_off') }}% Off)
					@endif
				</td>
				<td>&nbsp;</td>
				<td>
					<strong>
						@if (array_get($discount, 'amount_off'))
							-${{ number_format(abs(array_get($discount, 'amount_off') / 100), 2) }}
						@else
							-${{ number_format($invoice->subtotal * (array_get($discount, 'percent_off') / 100) / 100, 2) }}
						@endif
					</strong>
				</td>
			</tr>
		@endforeach
	@endif
	
	<!-- Display The Total -->
	@if ($invoice->total && $invoice->discounts)
		<tr>
			<td>&nbsp;</td>
			<td style="text-align: right;">Total:</td>
			<td><strong>${{ number_format($invoice->total / 100, 2) }}</strong></td>
		</tr>
	@endif
	
	<!-- Display Any Starting Balance -->
	@if ($invoice->starting_balance)
		<tr>
			<td>&nbsp;</td>
			<td style="text-align: right;">Starting Customer Balance:</td>
			<td>
				@if ($invoice->starting_balance >= 0)
					<strong>${{ number_format($invoice->starting_balance / 100, 2) }}</strong>
				@else
					<strong>-${{ number_format(abs($invoice->starting_balance) / 100, 2) }}</strong>
				@endif
			</td>
		</tr>
	@endif
	
	<!-- Display The Final Amount -->
	<tr style="border-top:2px solid #000;">
		<td>&nbsp;</td>
		<td style="text-align: right;"><strong>Amount Paid:</strong.</td>
		<td><strong>${{ number_format($invoice->amount / 100, 2) }}</strong></td>
	</tr>
</table>
