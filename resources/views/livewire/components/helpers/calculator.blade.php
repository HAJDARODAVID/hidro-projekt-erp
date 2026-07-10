<div >
    <div class="mb-2">
        <b>Koliko je</b> 
        <input type="number" class="form-control" style="width: 80px;display: inline" wire:model.blur='data.percentageOfAmount.percentage'><b>%</b>
        <b>od</b>
        <input type="number" class="form-control" style="width: 150px;display: inline" wire:model.blur='data.percentageOfAmount.amount'>
        <b>=</b>
        <input type="number" class="form-control" style="width: 150px;display: inline" disabled wire:model.live='data.percentageOfAmount.result'>
        <i>Primjer: Koliko je <b>35</b>% od <b>365,38</b> = <b>127,88</b></i>
    </div>

    <div class="mb-2"> 
        <input type="number" class="form-control" style="width: 150px;display: inline" wire:model.blur='data.percentageFromAmount.amountI'>
        <b>je koji postotak od</b>
        <input type="number" class="form-control" style="width: 150px;display: inline" wire:model.blur='data.percentageFromAmount.amountII'>
        <b>=</b>
        <input type="number" class="form-control" style="width: 80px;display: inline" disabled wire:model.live='data.percentageFromAmount.result'> <b>%</b>
        <i>Primjer: <b>75</b> je koji postotak od <b>100</b> = <b>75%</b></i>
    </div>

    <div class="mb-4">
        <input type="number" class="form-control" style="width: 150px;display: inline" wire:model.blur='data.amountFromPercentage.amount'>
        <b> je </b>
        <input type="number" class="form-control" style="width: 80px;display: inline" wire:model.blur='data.amountFromPercentage.percentage'>
        <b>% od </b>
        <input type="number" class="form-control" style="width: 150px;display: inline" disabled wire:model.live='data.amountFromPercentage.result'>
        <i>Primjer: <b>100</b> je <b>10</b>% od <b>1000</b></i>
    </div>

    <div class="mb-2">
        <b>Uvećaj</b>
        <input type="number" class="form-control" style="width: 150px;display: inline" wire:model.blur='data.enlargeAmountForPercentage.amount'>
        <b> za </b>
        <input type="number" class="form-control" style="width: 80px;display: inline" wire:model.blur='data.enlargeAmountForPercentage.percentage'>
        <b>% = </b>
        <input type="number" class="form-control" style="width: 150px;display: inline" disabled wire:model.live='data.enlargeAmountForPercentage.result'>
        <i>Primjer: Uvećaj <b>100</b> za <b>5</b>% = <b>105</b></i>
    </div>
    <div class="mb-2">
        <b>Smanji</b>
        <input type="number" class="form-control" style="width: 150px;display: inline" wire:model.blur='data.decreaseAmountForPercentage.amount'>
        <b> za </b>
        <input type="number" class="form-control" style="width: 80px;display: inline" wire:model.blur='data.decreaseAmountForPercentage.percentage'>
        <b>% = </b>
        <input type="number" class="form-control" style="width: 150px;display: inline" disabled wire:model.live='data.decreaseAmountForPercentage.result'>
        <i>Primjer: Smanji <b>100</b> za <b>5</b>% = <b>95</b></i>
    </div>
</div>
