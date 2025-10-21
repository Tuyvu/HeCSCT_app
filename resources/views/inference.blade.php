@extends('layouts.app')

@section('content')
<form id="inference-form" action="{{ route('inference.run') }}" method="POST">
    @csrf

    <div class="grid-container">
        <aside class="sidebar">

            <!-- 🔹 Chọn / Quản lý bộ luật -->
            <div class="card">
                <h2>Chọn bộ luật</h2>

                <select id="rule-set-selector" class="form-select">
                    <option value="">-- Chưa chọn bộ luật nào --</option>
                </select>

                <button type="button" id="new-rule-set" class="btn btn-primary mt-2">+ Tạo bộ luật mới</button>
                <button type="button" id="delete-rule-set" class="btn btn-danger mt-2">🗑 Xoá bộ luật</button>
            </div>

            <!-- 🔹 Modal tạo bộ luật -->
            <div id="new-rule-set-modal" class="modal-overlay hidden">
                <div class="modal-content">
                    <h3>Tạo bộ luật mới</h3>
                    <label>Tên bộ luật:</label>
                    <input type="text" id="rule-set-name" class="form-input" placeholder="VD: Luật động vật">
                    <label>Mô tả:</label>
                    <textarea id="rule-set-desc" class="form-textarea" placeholder="Mô tả bộ luật..."></textarea>
                    <div class="modal-actions">
                        <button id="cancel-new-set" type="button" class="btn btn-secondary">Hủy</button>
                        <button id="confirm-new-set" type="button" class="btn btn-primary">Tạo</button>
                    </div>
                </div>
            </div>

            <!-- 🔹 Nhập luật -->
            <div class="card">
                <h2>Nhập luật</h2>
                <button type="button" id="open-bulk-form" class="btn btn-primary">+ Thêm luật</button>
                <button type="button" id="import-button" class="btn btn-secondary">Import luật</button>

                <div id="rules-list" class="rules-facts-list"></div>
                <textarea id="rules-storage" name="rules" class="hidden"></textarea>
                <input type="file" id="file-importer" class="hidden" accept=".txt,.json,.docx">
            </div>

            <!-- 🔹 Modal nhập nhiều luật -->
            <div id="bulk-form" class="modal-overlay hidden">
                <div class="modal-content">
                    <h3>Nhập nhiều luật cùng lúc</h3>
                    <p>Mỗi dòng là một luật, ví dụ:<br>
                        <code>A ^ B → C</code><br>
                        <code>D → E</code>
                    </p>
                    <textarea id="bulk-rules-input" class="form-textarea" placeholder="Nhập các luật ở đây..."></textarea>
                    <div class="modal-actions">
                        <button id="cancel-bulk" type="button" class="btn btn-secondary">Hủy</button>
                        <button id="confirm-bulk" type="button" class="btn btn-primary">Xác nhận thêm</button>
                    </div>
                </div>
            </div>

            <!-- 🔹 Giả thiết -->
            <div class="card">
                <h2>Nhập giả thiết</h2>
                <input type="text" id="fact-input" class="form-input" placeholder="VD: a,b">
                <button type="button" id="add-fact-button" class="btn btn-success">+ Thêm giả thiết</button>
                <div id="facts-list" class="rules-facts-list"></div>
                <input type="text" id="facts-storage" name="facts" class="hidden">
            </div>

            <!-- 🔹 Kết luận -->
            <div id="goal-input-container" class="card">
                <h2>Nhập kết luận (Goal)</h2>
                <input type="text" name="goal" class="form-input" placeholder="VD: D — mục tiêu cần chứng minh hoặc đạt được.">
            </div>

            <!-- 🔹 Loại suy diễn -->
            <div class="card">
                <h2>Chọn loại suy diễn</h2>
                <div class="radio-group">
                    <label>
                        <input type="radio" id="forward" name="inference_type" value="forward" class="form-radio" checked>
                        Suy diễn tiến (Forward Chaining)
                    </label>
                    <label>
                        <input type="radio" id="backward" name="inference_type" value="backward" class="form-radio">
                        Suy diễn lùi (Backward Chaining)
                    </label>
                </div>
            </div>

            <!-- 🔹 Thao tác -->
            <div class="card">
                <h2>Nút thao tác</h2>
                <div class="button-group">
                    <button type="submit" class="btn btn-submit">Chạy suy diễn</button>
                    <button type="reset" class="btn btn-danger">Xoá dữ liệu</button>
                    <button type="button" id="save-button" class="btn btn-dark">💾 Lưu dữ liệu (F11)</button>
                </div>
            </div>

        </aside>

        <section class="main-content">
            <!-- 🔹 Đồ thị -->
            <div class="card">
                <div class="card-header">
                    <h2>Khu vực đồ thị</h2>
                    <div class="radio-group horizontal">
                        <label><input type="radio" name="graph_type" value="fpg" class="form-radio" checked> FPG</label>
                        <label><input type="radio" name="graph_type" value="rpg" class="form-radio"> RPG</label>
                    </div>
                </div>
                <div id="graph-container">
                    <p class="placeholder-text">Đồ thị sẽ hiển thị ở đây...</p>
                </div>
            </div>

            <!-- 🔹 Kết quả -->
            <div class="card">
                <h2>Kết quả suy diễn</h2>
                <div id="results-output">
                    <p><strong>Sự kiện mới:</strong> <span id="result-facts" class="result-facts">...</span></p>
                    <p><strong>Luật được kích hoạt:</strong> <span id="result-rules" class="result-rules">...</span></p>
                </div>
            </div>
        </section>
    </div>
</form>
@endsection
