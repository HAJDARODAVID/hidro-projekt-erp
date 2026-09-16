<div class="row">
    <div class="col-md">
        <x-ui.card loading="routeData">
            <table class="table table-fixed-rows">
                <tbody>
                    <tr>
                        <td ><b>ID</b></td>
                        <td>{{ $routeID }}</td>
                    </tr>
                    <tr>
                        <td><b>Title</b></td>
                        <td><x-ui.input size="sm" wModel='routeData.title' /></td>
                    </tr>
                    <tdr>
                        <td><b>Route name</b></td>
                        <td>
                            <x-ui.input size="sm" wModel='routeData.route_name' />
                        </td>
                    </tr>
                    <tr>
                        <td><b>Method</b></td>
                        <td>
                            <x-ui.input size="sm" wModel='routeData.method' />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="d-flex gap-1">
                                <b>Divider after</b>
                                <x-ui.tool-tips message="Show a vertical line after this tab in the module tab bar." />
                            </div>
                        </td>
                        <td>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" wire:model.change='routeData.divider_after' />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </x-ui.card>
        
    </div>
</div> 