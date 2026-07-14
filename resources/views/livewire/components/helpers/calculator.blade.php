<div >
    <div class="col col-md-7">
        <div class="row mb-2">
            <div class="col col-md-5">
                <div class="form-group">
                    <label for="amount1">Iznos bez PDV-a</label>
                    <input type="number" class="form-control" id="amount1" wire:model.blur='add.amount'>
                </div>
            </div>
            <div class="col col-md-2">
                <div class="form-group">
                    <label for="amount2">PDV [%]</label>
                    <input type="number" class="form-control" id="amount2" wire:model.blur='add.pdv'>
                </div>
            </div>
            <div class="col col-md-5">
                <div class="form-group">
                    <label for="amount2">Iznos sa PDV-om</label>
                    <input type="number" class="form-control" id="amount2" disabled wire:model.live='add.result'>
                </div>
            </div>
        </div>
        <div class="row mb-2">
            <div class="col col-md-5">
                <div class="form-group">
                    <label for="amount1">Iznos sa PDV-a</label>
                    <input type="number" class="form-control" id="amount1" wire:model.blur='remove.amount'>
                </div>
            </div>
            <div class="col col-md-2">
                <div class="form-group">
                    <label for="amount2">PDV [%]</label>
                    <input type="number" class="form-control" id="amount2" wire:model.blur='remove.pdv'>
                </div>
            </div>
            <div class="col col-md-5">
                <div class="form-group">
                    <label for="amount2">Iznos bez PDV-om</label>
                    <input type="number" class="form-control" id="amount2" disabled wire:model.live='remove.result'>
                </div>
            </div>
        </div>
    </div>
    <hr>
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
