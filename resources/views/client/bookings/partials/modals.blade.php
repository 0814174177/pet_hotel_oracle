<div class="booking-modal" id="serviceModal" hidden>
    <div class="booking-modal-panel">
        <div class="booking-modal-head">
            <div>
                <h3>Dịch vụ cho <span id="servicePetName">thú cưng</span></h3>
                <p>Chọn dịch vụ muốn thêm cho bé.</p>
            </div>
            <button type="button" class="booking-modal-close" data-close-modal>
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="service-modal-list" id="serviceModalList"></div>

        <div class="booking-modal-actions">
            <button type="button" class="modal-light-btn" data-close-modal>Hủy</button>
            <button type="button" class="modal-orange-btn" id="saveServiceBtn">Lưu dịch vụ</button>
        </div>
    </div>
</div>

<div class="booking-modal" id="bookingErrorModal" hidden>
    <div class="booking-modal-panel booking-modal-panel--compact">
        <div class="booking-modal-head">
            <div>
                <h3>Không thể tiếp tục đặt phòng</h3>
                <p id="bookingErrorMessage">Vui lòng kiểm tra lại thông tin đặt phòng.</p>
            </div>
            <button type="button" class="booking-modal-close" data-close-modal>
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="booking-modal-actions">
            <button type="button" class="modal-orange-btn" data-close-modal>Đã hiểu</button>
        </div>
    </div>
</div>
