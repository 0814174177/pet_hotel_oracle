@extends('layouts.client')

@section('title', 'Chính sách & Điều khoản')

@section('content')

<section class="policy-page">
    <div class="policy-container">

        <h1 class="policy-page-title">Chính sách & Điều khoản</h1>

        <div class="policy-layout">

            {{-- Sidebar --}}
            <aside class="policy-sidebar">
                <h3>Danh mục</h3>

                <a href="#gioi-thieu" class="policy-menu-item active">
                    <i class="fa-regular fa-file-lines"></i>
                    Giới thiệu
                </a>

                <a href="#chinh-sach-dat-phong" class="policy-menu-item">
                    <i class="fa-regular fa-calendar-check"></i>
                    Chính sách đặt phòng
                </a>

                <a href="#chinh-sach-huy-phong" class="policy-menu-item">
                    <i class="fa-regular fa-calendar-xmark"></i>
                    Chính sách hủy phòng
                </a>

                <a href="#yeu-cau-suc-khoe" class="policy-menu-item">
                    <i class="fa-solid fa-shield-heart"></i>
                    Yêu cầu sức khỏe
                </a>

                <a href="#thanh-toan" class="policy-menu-item">
                    <i class="fa-regular fa-credit-card"></i>
                    Quy định và hình thức thanh toán
                </a>

                <a href="#bao-hanh" class="policy-menu-item">
                    <i class="fa-regular fa-circle-check"></i>
                    Chính sách bảo hành
                </a>

                <a href="#van-chuyen" class="policy-menu-item">
                    <i class="fa-solid fa-truck"></i>
                    Chính sách vận chuyển và giao nhận
                </a>

                <a href="#bao-mat" class="policy-menu-item">
                    <i class="fa-regular fa-user"></i>
                    Chính sách bảo mật thông tin cá nhân
                </a>

                <a href="#hoan-tien" class="policy-menu-item">
                    <i class="fa-solid fa-rotate-left"></i>
                    Chính sách đổi/trả hàng và hoàn tiền
                </a>

                <a href="#dieu-khoan-su-dung" class="policy-menu-item">
                    <i class="fa-solid fa-scale-balanced"></i>
                    Điều khoản sử dụng
                </a>
            </aside>

            {{-- Content --}}
            <main class="policy-content-card">

                <div class="policy-content-header">
                    <div>
                        <h2 id="gioi-thieu">Giới thiệu</h2>
                        <p>Thông tin tổng quan về dịch vụ và phạm vi áp dụng chính sách.</p>
                    </div>

                    <span class="policy-last-updated">
                        Cập nhật lần cuối: 15/03/2026
                    </span>
                </div>

                <div class="policy-block">
                    <h3>Giới thiệu về Pet Hotel</h3>
                    <p>
                        Pet Hotel cung cấp dịch vụ lưu trú, chăm sóc và hỗ trợ các nhu cầu cơ bản
                        cho thú cưng trong thời gian chủ nuôi vắng nhà. Các chính sách dưới đây
                        được xây dựng nhằm đảm bảo quyền lợi của khách hàng, sự an toàn của thú cưng
                        và tính minh bạch trong quá trình sử dụng dịch vụ.
                    </p>
                </div>

                <div class="policy-block" id="chinh-sach-dat-phong">
                    <h3>Chính sách đặt phòng</h3>

                    <h4>1. Thông tin đặt phòng</h4>
                    <p>
                        Khách hàng cần cung cấp đầy đủ thông tin liên hệ, thông tin thú cưng,
                        chi nhánh lưu trú, loại phòng, ngày nhận phòng và ngày trả phòng khi đặt phòng.
                    </p>

                    <h4>2. Xác nhận đặt phòng</h4>
                    <p>
                        Đơn đặt phòng được xem là hợp lệ khi khách hàng hoàn tất các thông tin bắt buộc
                        và nhận được xác nhận từ hệ thống hoặc nhân viên Pet Hotel.
                    </p>

                    <h4>3. Thay đổi thông tin đặt phòng</h4>
                    <p>
                        Khách hàng có thể yêu cầu thay đổi thời gian lưu trú, loại phòng hoặc dịch vụ
                        đi kèm trước ngày nhận phòng. Việc thay đổi phụ thuộc vào tình trạng phòng trống
                        tại chi nhánh.
                    </p>
                </div>

                <div class="policy-block" id="chinh-sach-huy-phong">
                    <h3>Chính sách hủy phòng</h3>

                    <h4>1. Điều kiện hủy phòng</h4>
                    <p>
                        Khách hàng có thể yêu cầu hủy phòng trước thời gian nhận phòng. Pet Hotel sẽ kiểm tra
                        trạng thái đơn đặt phòng và phương thức thanh toán để đưa ra hướng xử lý phù hợp.
                    </p>

                    <h4>2. Phí hủy phòng</h4>
                    <ul>
                        <li>Đơn chưa thanh toán có thể được hủy theo quy định của hệ thống.</li>
                        <li>Đơn đã thanh toán có thể phát sinh phí hủy tùy theo thời điểm yêu cầu hủy.</li>
                        <li>Trường hợp hủy sát ngày nhận phòng, Pet Hotel có thể không hoàn lại toàn bộ chi phí.</li>
                    </ul>
                </div>

                <div class="policy-block" id="yeu-cau-suc-khoe">
                    <h3>Yêu cầu sức khỏe</h3>

                    <h4>1. Tình trạng sức khỏe trước khi lưu trú</h4>
                    <p>
                        Thú cưng cần có tình trạng sức khỏe ổn định trước khi được tiếp nhận lưu trú.
                        Chủ nuôi cần thông báo trước nếu thú cưng có tiền sử bệnh, dị ứng, đang điều trị
                        hoặc cần sử dụng thuốc trong thời gian lưu trú.
                    </p>

                    <h4>2. Vaccine và phòng bệnh</h4>
                    <p>
                        Pet Hotel khuyến khích chủ nuôi cung cấp thông tin tiêm phòng hoặc sổ sức khỏe
                        của thú cưng. Trong một số trường hợp, chi nhánh có thể yêu cầu kiểm tra thông tin
                        vaccine trước khi nhận lưu trú.
                    </p>

                    <h4>3. Từ chối tiếp nhận</h4>
                    <p>
                        Pet Hotel có quyền từ chối tiếp nhận thú cưng có dấu hiệu bệnh truyền nhiễm,
                        tình trạng sức khỏe bất thường nghiêm trọng hoặc có nguy cơ gây mất an toàn
                        cho thú cưng khác và nhân viên.
                    </p>
                </div>

                <div class="policy-block" id="thanh-toan">
                    <h3>Quy định và hình thức thanh toán</h3>

                    <h4>1. Hình thức thanh toán</h4>
                    <ul>
                        <li>Thanh toán tiền mặt trực tiếp tại chi nhánh khi nhận phòng.</li>
                    </ul>

                    <h4>2. Thời điểm thanh toán</h4>
                    <p>
                        Khách hàng thanh toán trực tiếp tại chi nhánh theo thông tin đơn đặt phòng đã được xác nhận.
                    </p>

                    <h4>3. Mã giảm giá</h4>
                    <p>
                        Mã giảm giá chỉ được áp dụng khi còn hiệu lực, đúng điều kiện sử dụng và được hệ thống
                        xác nhận trong quá trình thanh toán.
                    </p>
                </div>

                <div class="policy-block" id="bao-hanh">
                    <h3>Chính sách bảo hành</h3>
                    <p>
                        Đối với các dịch vụ chăm sóc thú cưng, Pet Hotel cam kết thực hiện đúng nội dung
                        dịch vụ đã xác nhận. Nếu phát sinh sai sót từ phía hệ thống hoặc nhân viên,
                        khách hàng có thể liên hệ để được hỗ trợ kiểm tra và xử lý phù hợp.
                    </p>
                </div>

                <div class="policy-block" id="van-chuyen">
                    <h3>Chính sách vận chuyển và giao nhận</h3>
                    <p>
                        Trong trường hợp Pet Hotel có hỗ trợ đưa đón thú cưng, thời gian và địa điểm
                        giao nhận sẽ được xác nhận trước với khách hàng. Chủ nuôi cần bàn giao thú cưng
                        đúng thời gian đã hẹn và cung cấp đầy đủ vật dụng cần thiết nếu có.
                    </p>
                </div>

                <div class="policy-block" id="bao-mat">
                    <h3>Chính sách bảo mật thông tin cá nhân</h3>

                    <h4>1. Thông tin được thu thập</h4>
                    <ul>
                        <li>Họ tên, số điện thoại, email và địa chỉ của khách hàng.</li>
                        <li>Thông tin thú cưng như tên, giống, cân nặng, tình trạng sức khỏe và ghi chú chăm sóc.</li>
                        <li>Thông tin đặt phòng, thanh toán và lịch sử sử dụng dịch vụ.</li>
                    </ul>

                    <h4>2. Mục đích sử dụng thông tin</h4>
                    <p>
                        Thông tin cá nhân được sử dụng để xác nhận đặt phòng, liên hệ với khách hàng,
                        chăm sóc thú cưng, xử lý thanh toán và hỗ trợ khách hàng khi cần thiết.
                    </p>

                    <h4>3. Cam kết bảo mật</h4>
                    <p>
                        Pet Hotel cam kết không chia sẻ thông tin cá nhân của khách hàng cho bên thứ ba
                        nếu không có sự đồng ý, trừ trường hợp cần thiết theo quy định pháp luật hoặc
                        phục vụ xử lý tình huống khẩn cấp liên quan đến thú cưng.
                    </p>
                </div>

                <div class="policy-block" id="hoan-tien">
                    <h3>Chính sách đổi/trả hàng và hoàn tiền</h3>

                    <h4>1. Hủy hoặc thay đổi lịch đặt phòng</h4>
                    <p>
                        Khách hàng có thể yêu cầu thay đổi hoặc hủy lịch đặt phòng trước thời gian nhận phòng.
                        Mức phí hủy hoặc điều kiện hoàn tiền sẽ phụ thuộc vào thời điểm hủy và trạng thái đơn đặt phòng.
                    </p>

                    <h4>2. Hoàn tiền</h4>
                    <ul>
                        <li>Hoàn tiền được xem xét khi khách hàng hủy đúng điều kiện quy định.</li>
                        <li>Trường hợp dịch vụ không thể thực hiện do lỗi từ Pet Hotel, khách hàng sẽ được hỗ trợ hoàn tiền hoặc đổi lịch.</li>
                        <li>Thời gian xử lý hoàn tiền phụ thuộc vào phương thức thanh toán đã sử dụng.</li>
                    </ul>
                </div>

                <div class="policy-block" id="dieu-khoan-su-dung">
                    <h3>Điều khoản sử dụng</h3>

                    <h4>1. Trách nhiệm của khách hàng</h4>
                    <ul>
                        <li>Cung cấp thông tin chính xác khi đăng ký, đặt phòng và thanh toán.</li>
                        <li>Thông báo trung thực về tình trạng sức khỏe, thói quen và nhu cầu đặc biệt của thú cưng.</li>
                        <li>Tuân thủ các quy định của Pet Hotel trong quá trình sử dụng dịch vụ.</li>
                    </ul>

                    <h4>2. Trách nhiệm của Pet Hotel</h4>
                    <ul>
                        <li>Cung cấp dịch vụ theo đúng thông tin đã xác nhận với khách hàng.</li>
                        <li>Đảm bảo môi trường lưu trú sạch sẽ, an toàn và phù hợp cho thú cưng.</li>
                        <li>Thông báo cho khách hàng khi có vấn đề phát sinh liên quan đến thú cưng hoặc đơn đặt phòng.</li>
                    </ul>

                    <h4>3. Thay đổi điều khoản</h4>
                    <p>
                        Pet Hotel có thể cập nhật nội dung chính sách và điều khoản sử dụng để phù hợp
                        với hoạt động thực tế. Các thay đổi sẽ được cập nhật trên hệ thống để khách hàng
                        có thể theo dõi.
                    </p>
                </div>

            </main>

        </div>
    </div>
</section>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const menuItems = document.querySelectorAll('.policy-menu-item');
        const sections = [];

        menuItems.forEach(function (item) {
            const targetId = item.getAttribute('href');

            if (targetId && targetId.startsWith('#')) {
                const section = document.querySelector(targetId);

                if (section) {
                    sections.push({
                        id: targetId,
                        element: section,
                        menu: item
                    });
                }
            }
        });

        function setActiveMenu() {
            let currentSection = sections[0];

            sections.forEach(function (section) {
                const rect = section.element.getBoundingClientRect();

                if (rect.top <= 160) {
                    currentSection = section;
                }
            });

            menuItems.forEach(function (item) {
                item.classList.remove('active');
            });

            if (currentSection) {
                currentSection.menu.classList.add('active');
            }
        }

        window.addEventListener('scroll', setActiveMenu);
        setActiveMenu();
    });
</script>
@endpush
@endsection
