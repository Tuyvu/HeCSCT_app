<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rules;
use App\Models\SetRule;
use Illuminate\Support\Facades\Http;
// use App\Helpers\Formula;

class InferenceRuleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $rules = Rules::all();
        $setRules = SetRule::all();
        // dd($setRules);
        return view('chat.inference', compact('rules', 'setRules'));
    }

    /**
     * Show the form for creating a new resource.
     */
    // public function create(Request $request)
    // {

    //     return view('chat.create');
    // }

    /**
     * Store a newly created resource in storage.
     */
   function convertToPythonFormula($formula) {
    // 1. Bỏ dấu '=' và lấy phần bên phải
    $formula = trim(explode('=', $formula)[1] ?? $formula);

    // 2. Thay ký tự toán học đặc biệt
    $replacements = [
        '√' => 'sqrt',
        '·' => '*',
        '^' => '**',
        '²' => '**2',
        '³' => '**3',
        ',' => '.',
    ];
    $formula = str_replace(array_keys($replacements), array_values($replacements), $formula);

    // 3. Thêm dấu * giữa các biến, ví dụ: 2ab -> 2*a*b, cosC -> cos(C)
    // 3.1 Thêm * giữa số và chữ
    $formula = preg_replace('/(\d)([a-zA-Z])/', '$1*$2', $formula);
    // 3.2 Thêm * giữa 2 chữ liền nhau (tránh các hàm như cos, sin)
    $formula = preg_replace('/(?<!cos)(?<!sin)(?<!tan)([a-zA-Z])([A-Z])/', '$1*$2', $formula);
    // 3.3 Đảm bảo hàm lượng giác có ngoặc
    $formula = preg_replace('/(cos|sin|tan)([A-Za-z])/', '$1(\2)', $formula);

    return trim($formula);
}

    public function store(Request $request)
    {

        $rules = $request->input('rules', []);
        $event = $request->input('event');
        $conclusion = $request->input('conclusion');
        $type = $request->input('type');
        $graphType = $request->input('graph_type');

        // 🔄 Chuyển đổi tất cả công thức
        foreach ($rules as &$rule) {
            if (!empty($rule['formula'])) {
                $rule['converted_formula'] = $this->convertToPythonFormula($rule['formula']);
            }
    }
    // dd($event, $conclusion, $rules);
     $response = Http::post('http://python-appsra:5000/infer', [
    'rules' => $rules,
    'event' => $event,
    'conclusion' => $conclusion,
    'type' => $type,
    'graph_type' => $graphType
    
]);
    $result = $response->json();
    // dd($result);

    // Debug xem đã đổi chưa
    // return response()->json($rules);

    // // Giả lập kết quả suy diễn
    //     $result = [
    //         'new_events' => 'D, E',
    //         'active_rules' => 'R1, R3',
    //         'converted_rules' => $rules
    //     ];

        return response()->json($result);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
