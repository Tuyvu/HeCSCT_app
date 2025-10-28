<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>HỆ THỐNG SUY DIỄN TRI THỨC</title>
  <script src="https://unpkg.com/cytoscape/dist/cytoscape.min.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

  <style>
    * { box-sizing: border-box; font-family: 'Poppins', sans-serif; }
    body {
      margin: 0; height: 100vh; display: flex; flex-direction: column;
      background-color: #f3f4f6;
    }
    header {
      background: linear-gradient(90deg, #3b82f6, #6366f1);
      color: white; padding: 15px; text-align: center;
      font-size: 1.6em; font-weight: 600;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1); flex-shrink: 0;
    }
    .main-layout { flex: 1; display: flex; overflow: hidden; width: 100%; }
    aside {
      flex-basis: 25%; background: white; border-right: 1px solid #ddd;
      padding: 20px; overflow-y: auto;
    }
    main {
      flex: 1; background: #f9fafb; padding: 25px;
      overflow-y: auto; min-width: 0;
    }
    footer {
      background: #1f2937; color: white; text-align: center;
      padding: 10px; font-size: 0.9em;
    }
    #active-rules div {
      background: #f9fafb;
      border-left: 4px solid #3b82f6;
      padding: 10px 15px;
      border-radius: 6px;
      white-space: pre-wrap;
      font-family: 'Courier New', monospace;
    }

  </style>
</head>

<body>
  <header>HỆ THỐNG SUY DIỄN TRI THỨC</header>

  <div class="main-layout">
    <aside>
      <h2 class="text-lg font-semibold text-blue-600 mb-3">🧮 Quản lý Bộ Luật</h2>

      <!-- Nhập luật -->
      <div class="bg-gray-50 p-3 rounded-xl mb-4">
        <textarea id="ruleInput" rows="6"
          placeholder="Nhập hoặc dán luật theo định dạng:&#10;1 | a,b,C | c | c = √(a² + b² - 2ab·cosC)"
          class="w-full p-2 border rounded-lg resize-none focus:ring-2 focus:ring-blue-400"></textarea>

        <div class="flex justify-between items-center mt-3">
          <input type="file" id="fileInput" accept=".txt,.csv"
            class="block w-1/2 text-sm text-gray-600 file:mr-3 file:py-1 file:px-3 file:rounded-full file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
          <button id="parseBtn" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">🔍 Phân tích</button>
        </div>
      </div>

      <!-- Danh sách luật -->
      <div class="bg-gray-50 p-3 rounded-xl mb-5">
        <div class="flex justify-between items-center mb-2">
          <h3 class="text-md font-semibold text-gray-700">📋 Danh sách luật</h3>
          <button id="addRuleBtn" class="bg-green-500 text-white px-3 py-1 rounded-md text-sm hover:bg-green-600">+ Thêm luật</button>
        </div>

        <div class="overflow-y-auto max-h-[300px] border rounded-lg">
          <table id="rulesTable" class="w-full text-sm text-left border-collapse">
            <thead class="bg-blue-100">
              <tr>
                <th class="p-2 border text-center">#</th>
                <th class="p-2 border">Đầu vào</th>
                <th class="p-2 border">Đầu ra</th>
                <th class="p-2 border">Công thức</th>
                <th class="p-2 border text-center">Thao tác</th>
              </tr>
            </thead>
            <tbody id="rulesBody" class="bg-white"></tbody>
          </table>
        </div>
      </div>

      <hr class="my-4 border-gray-300">

      <!-- Giả thiết và kết luận -->
      <h2 class="font-semibold mb-2">Nhập giả thiết</h2>
      <input type="text" id="event" name="assumption" placeholder="Nhập giả thiết..." class="border rounded-lg p-2 w-full mb-2">
      {{-- <button class="w-full bg-green-500 text-white py-2 rounded-lg hover:bg-green-600 mb-3">+ Thêm giả thiết</button> --}}

      <h2 class="font-semibold mb-2">Nhập kết luận</h2>
      <input type="text" id="conclusion" name="conclusion" placeholder="Nhập kết luận..." class="border rounded-lg p-2 w-full mb-2">
      {{-- <button class="w-full bg-green-500 text-white py-2 rounded-lg hover:bg-green-600 mb-3">+ Thêm kết luận</button> --}}

      <h2 class="font-semibold mb-2">Chọn loại suy diễn</h2>
      <label class="block"><input type="radio" name="type" value="forward" checked> Suy diễn tiến</label>
      <label class="block mb-3"><input type="radio" name="type" value="backward"> Suy diễn lùi</label>

      <h2 class="font-semibold mb-2">Nút thao tác</h2>
      <button class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 mb-2">▶️ Chạy suy diễn</button>
      <button class="w-full bg-red-500 text-white py-2 rounded-lg hover:bg-red-600 mb-2">🗑️ Xóa dữ liệu</button>
      <button class="w-full bg-gray-600 text-white py-2 rounded-lg hover:bg-gray-700">💾 Lưu tri thức</button>
    </aside>

    <main>
      <div class="bg-white p-5 rounded-xl shadow-md mb-5">
        <h2 class="text-lg font-semibold mb-3">Khu vực đồ thị (FPG / RPG)</h2>
        <div class="radio-group horizontal">
          <label><input type="radio" name="graph_type" value="fpg" class="form-radio" checked> FPG</label>
          <label><input type="radio" name="graph_type" value="rpg" class="form-radio"> RPG</label>
        </div>
        <div id="cy" style="width:100%; height:65vh; border:1px solid #ddd; border-radius:8px;"></div>
      </div>

      <div class="bg-white p-5 rounded-xl shadow-md">
        <h2 class="text-lg font-semibold mb-3">Kết quả</h2>
        <p><strong>Giả thiết:</strong> <span id="new-events">A, C, D</span></p>
        <p><strong>Các Bước:</strong> <span id="active-rules">R1, R3</span></p>
        <p><strong>Kết quả</strong> <span id="r-rules"></span></p>
      </div>
    </main>
  </div>

  <footer>© 2025 - Nhóm X - Môn Công nghệ tri thức</footer>

  <script>
    const parseBtn = document.getElementById("parseBtn");
    const ruleInput = document.getElementById("ruleInput");
    const rulesBody = document.getElementById("rulesBody");
    const fileInput = document.getElementById("fileInput");
    const addRuleBtn = document.getElementById("addRuleBtn");
    let rules = [];

    // Đọc file txt/csv
    fileInput.addEventListener("change", (e) => {
      const file = e.target.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = (event) => {
        ruleInput.value = event.target.result;
      };
      reader.readAsText(file);
    });

    // Phân tích dữ liệu nhập
    parseBtn.addEventListener("click", () => {
      const lines = ruleInput.value.trim().split("\n");
      rules = lines.map(line => {
        const parts = line.split("|").map(x => x.trim());
        return { input: parts[1], output: parts[2], formula: parts[3] };
      });
      updateRuleIDs();
      renderTable();
    });

    // Cập nhật số thứ tự
    function updateRuleIDs() {
      rules = rules.map((r, index) => ({ id: index + 1, ...r }));
    }

    // Thêm luật thủ công
    addRuleBtn.addEventListener("click", () => {
      Swal.fire({
        title: "➕ Thêm luật mới",
        html: `
          <input id="rule_input" class="swal2-input" placeholder="Đầu vào (vd: a,b,c)">
          <input id="rule_output" class="swal2-input" placeholder="Đầu ra (vd: d)">
          <input id="rule_formula" class="swal2-input" placeholder="Công thức (vd: d = a + b + c)">
        `,
        confirmButtonText: "Thêm",
        focusConfirm: false,
        preConfirm: () => {
          const input = document.getElementById("rule_input").value.trim();
          const output = document.getElementById("rule_output").value.trim();
          const formula = document.getElementById("rule_formula").value.trim();
          if (!input || !output) {
            Swal.showValidationMessage("Vui lòng nhập đủ thông tin!");
            return false;
          }
          return { input, output, formula };
        }
      }).then(result => {
        if (result.isConfirmed) {
          rules.push(result.value);
          updateRuleIDs();
          renderTable();
        }
      });
    });

    // Hiển thị bảng
    function renderTable() {
      rulesBody.innerHTML = "";
      rules.forEach((r, index) => {
        const row = document.createElement("tr");
        row.innerHTML = `
          <td class="border p-2 text-center">${r.id}</td>
          <td class="border p-2">${r.input}</td>
          <td class="border p-2">${r.output}</td>
          <td class="border p-2">${r.formula}</td>
          <td class="border p-2 text-center">
            <button onclick="editRule(${index})" class="text-blue-600 hover:underline">Sửa</button> |
            <button onclick="deleteRule(${index})" class="text-red-600 hover:underline">Xóa</button>
          </td>`;
        rulesBody.appendChild(row);
      });
    }

    // Sửa luật
    function editRule(index) {
      const rule = rules[index];
      Swal.fire({
        title: "✏️ Chỉnh sửa luật",
        html: `
          <input id="input" class="swal2-input" placeholder="Đầu vào" value="${rule.input}">
          <input id="output" class="swal2-input" placeholder="Đầu ra" value="${rule.output}">
          <input id="formula" class="swal2-input" placeholder="Công thức" value="${rule.formula}">
        `,
        confirmButtonText: "Lưu",
        preConfirm: () => {
          rules[index].input = document.getElementById("input").value;
          rules[index].output = document.getElementById("output").value;
          rules[index].formula = document.getElementById("formula").value;
        }
      }).then(() => renderTable());
    }

    // Xóa luật
    function deleteRule(index) {
      Swal.fire({
        title: "Xóa luật này?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Xóa",
        cancelButtonText: "Hủy"
      }).then(result => {
        if (result.isConfirmed) {
          rules.splice(index, 1);
          updateRuleIDs(); // cập nhật lại số thứ tự
          renderTable();
        }
      });
    }
     // === XỬ LÝ NÚT "CHẠY SUY DIỄN" ===
  const runBtn = document.querySelector('button.bg-blue-600'); // nút ▶️ Chạy suy diễn

  runBtn.addEventListener("click", async () => {
    // Lấy dữ liệu đầu vào
    const event = document.getElementById("event").value.trim();
    const conclusion = document.getElementById("conclusion").value.trim();
    const type = document.querySelector('input[name="type"]:checked').value;
    const graph_type = document.querySelector('input[name="graph_type"]:checked').value;

    if (rules.length === 0) {
      Swal.fire("⚠️ Chưa có luật nào!", "Vui lòng nhập hoặc phân tích luật trước.", "warning");
      return;
    }

    if (!event || !conclusion) {
      Swal.fire("⚠️ Thiếu dữ liệu!", "Vui lòng nhập giả thiết và kết luận.", "warning");
      return;
    }

    // Gói dữ liệu gửi sang Laravel
    const data = {
      rules: rules, // mảng các luật [{id, input, output, formula}, ...]
      event: event, // giả thiết
      conclusion: conclusion, // kết luận
      type: type, // forward/backward
      graph_type: graph_type // loại đồ thị
    };
    let result = {};
    console.log(data);
    try {
      Swal.fire({
        title: "⏳ Đang chạy suy diễn...",
        text: "Vui lòng đợi trong giây lát...",
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
      });

      // Gửi qua route Laravel
      const response = await fetch("{{ route('inferapi.run') }}", {
          method: "POST",
          headers: {
              "Content-Type": "application/json",
              "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
          },
          body: JSON.stringify(data)
      });

      if (!response.ok) throw new Error("Lỗi phản hồi từ server");

      result = await response.json();
      console.log(result);

      Swal.close();

      // Hiển thị kết quả trả về
      document.getElementById("new-events").textContent = event || "Không có";
      const solutionText = (result.solution_steps || "Không có bước suy diễn.")
      .replace(/\n/g, "<br>")
      .replace(/\s{2,}/g, "&nbsp;&nbsp;"); // giữ khoảng trắng để căn đẹp hơn
      document.getElementById("active-rules").innerHTML = `<div class="font-mono text-sm leading-relaxed text-gray-800">${solutionText}</div>`;
      document.getElementById("r-rules").textContent = result.conclusion || "Không có";

      Swal.fire("✅ Hoàn tất!", "Suy diễn đã được thực hiện thành công.", "success");
    } catch (error) {
      console.error(error);
      Swal.fire("❌ Lỗi!", "Không thể kết nối đến server.", "error");
    }
    // Mô phỏng đồ thị
    const cy = cytoscape({
  container: document.getElementById('cy'),
  elements: [...result.graph.nodes, ...result.graph.edges],
  style: [
    {
      selector: 'node',
      style: {
        'background-color': '#3b82f6',
        'label': 'data(label)',
        'color': 'white',
        'text-valign': 'center',
        'width': '50px',
        'height': '50px',
        'font-size': '12px'
      }
    },
    {
      selector: 'edge',
      style: {
        'width': 2,
        'line-color': '#9ca3af',
        'target-arrow-shape': 'triangle',
        'target-arrow-color': '#9ca3af',
        'curve-style': 'bezier'

      }
    }
  ],
  layout: { name: 'breadthfirst', directed: true }
});
  });
    
  </script>
</body>
</html>
