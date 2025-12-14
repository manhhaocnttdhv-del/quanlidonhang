// Danh sách 34 tỉnh thành Việt Nam (mới nhất sau khi sáp nhập)
const VIETNAM_PROVINCES = [
    'An Giang',
    'Bà Rịa - Vũng Tàu',
    'Bạc Liêu',
    'Bắc Giang',
    'Bắc Kạn',
    'Bắc Ninh',
    'Bến Tre',
    'Bình Định',
    'Bình Dương',
    'Bình Phước',
    'Bình Thuận',
    'Cà Mau',
    'Cao Bằng',
    'Cần Thơ',
    'Đà Nẵng',
    'Đắk Lắk',
    'Đắk Nông',
    'Điện Biên',
    'Đồng Nai',
    'Đồng Tháp',
    'Gia Lai',
    'Hà Giang',
    'Hà Nam',
    'Hà Nội',
    'Hà Tĩnh',
    'Hải Dương',
    'Hải Phòng',
    'Hậu Giang',
    'Hòa Bình',
    'Hưng Yên',
    'Khánh Hòa',
    'Kiên Giang',
    'Kon Tum',
    'Lai Châu',
    'Lâm Đồng',
    'Lạng Sơn',
    'Lào Cai',
    'Long An',
    'Nam Định',
    'Nghệ An',
    'Ninh Bình',
    'Ninh Thuận',
    'Phú Thọ',
    'Phú Yên',
    'Quảng Bình',
    'Quảng Nam',
    'Quảng Ngãi',
    'Quảng Ninh',
    'Quảng Trị',
    'Sóc Trăng',
    'Sơn La',
    'Tây Ninh',
    'Thái Bình',
    'Thái Nguyên',
    'Thanh Hóa',
    'Thừa Thiên Huế',
    'Tiền Giang',
    'TP. Hồ Chí Minh',
    'Trà Vinh',
    'Tuyên Quang',
    'Vĩnh Long',
    'Vĩnh Phúc',
    'Yên Bái'
];

// Hàm populate tỉnh thành vào select
function populateProvinces(selectElement) {
    selectElement.innerHTML = '<option value="">-- Chọn Tỉnh/Thành phố --</option>';
    VIETNAM_PROVINCES.forEach(province => {
        const option = document.createElement('option');
        option.value = province;
        option.textContent = province;
        selectElement.appendChild(option);
    });
}

// Sự kiện khi chọn tỉnh thành (có thể mở rộng để load quận/huyện sau)
document.addEventListener('DOMContentLoaded', function() {
    const provinceSelect = document.getElementById('province_select');
    if (provinceSelect) {
        populateProvinces(provinceSelect);
        
        // Nếu có giá trị cũ (old input), set lại
        const oldValue = provinceSelect.getAttribute('data-old-value');
        if (oldValue) {
            provinceSelect.value = oldValue;
        }
    }
});

