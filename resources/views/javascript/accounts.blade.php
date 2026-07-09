var accountInfo = [];
@foreach($accounts as $id => $account)
    accountInfo[{{ $id }}] = {preferredCurrency: "{{ $account->preferredCurrency }}", name: "{{ $account->name }}"};
@endforeach
