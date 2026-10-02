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
                                <b>Divider</b>
                                <x-ui.tool-tips message="Show a vertical line on the left or right side of this tab in the module tab bar. It is shown regardless of the neighbouring tab's setting." />
                            </div>
                        </td>
                        <td>
                            <x-ui.select wModel='routeData.divider' class="form-select-sm"
                                :options="['' => 'None', 'left' => 'Left', 'right' => 'Right']" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </x-ui.card>
        
    </div>
</div> 