<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $entries = $request->input('entries', 10); // Pagination
        $wos = Warehouse::paginate($entries);
        $totalwarehouse = Warehouse::count();
        $shopname = User::whereNotNull('shopname')->value('shopname');
        $address = User::whereNotNull('address')->value('address');
        return view('admin.warehouse.index', compact('shopname','address','wos', 'entries', 'totalwarehouse'));
    }

    public function store(Request $request){
        $data = $request->except("_token");
        $request->validate([
            'name' => 'required',
            'address' => 'required',
            'description' => 'required'
        ]);

        $isWOExist = Warehouse::where('name', $request->name)->exists();

        if ($isWOExist) {
            return back()
            ->withErrors([
                'name' => 'This warehouse already exist'
            ])

            ->withInput();
        }

        Warehouse::create($data);

        return redirect()->route('admin.warehouse')->with('success','Warehouse created');
    }

    public function update(Request $request, $id){
        $data = $request->except("_token");
        $request->validate([
            'name' => 'required',
            'address' => 'required',
            'description' => 'required'
        ]);

        $wos = Warehouse::find($id);
        $wos->update($data);
        return redirect()->route('admin.warehouse')->with('success', 'Warehouse updated');
    }

    public function destroy($id)
    {
        Warehouse::find($id)->delete();

        return redirect()->route('admin.warehouse')->with('success', 'Warehouse deleted');
    }
}