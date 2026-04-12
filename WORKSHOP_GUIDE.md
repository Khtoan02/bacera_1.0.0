# 📚 Hướng Dẫn Quản Lý Workshop — Bacera

> **Phiên bản:** 2.x | **Cập nhật:** 04/2026  
> **Đối tượng:** Quản trị viên Bacera

---

## Mục lục

1. [Tổng quan kiến trúc](#1-tổng-quan-kiến-trúc)
2. [Cơ sở dữ liệu](#2-cơ-sở-dữ-liệu)
3. [Hướng dẫn Admin — Quản lý Workshop](#3-hướng-dẫn-admin--quản-lý-workshop)
4. [Hướng dẫn Admin — Ca học (Slots)](#4-hướng-dẫn-admin--ca-học-slots)
5. [Hướng dẫn Admin — Học viên & Điểm danh](#5-hướng-dẫn-admin--học-viên--điểm-danh)
6. [Quy trình đặt chỗ (Frontend)](#6-quy-trình-đặt-chỗ-frontend)
7. [Mã khuyến mãi & Thanh toán](#7-mã-khuyến-mãi--thanh-toán)
8. [Kiểm tra & Xử lý sự cố](#8-kiểm-tra--xử-lý-sự-cố)
9. [Roadmap & Đề xuất nâng cấp](#9-roadmap--đề-xuất-nâng-cấp)

---

## 1. Tổng quan kiến trúc

Hệ thống Workshop Bacera sử dụng **kiến trúc Hybrid**:

```
WordPress CPT (Workshop)          Custom SQL Tables
─────────────────────────         ──────────────────────────────────────
post_title, post_content,    ←→   bacera_workshop_slots    (Ca học)
post_status, featured image       bacera_workshop_bookings  (Đặt chỗ)
                                  bacera_workshop_reviews   (Đánh giá)
Post Meta: _price, _duration,
           _trainer, _tagline,
           _short_desc, _includes,
           _highlights, _gallery,
           _thumbnail_url/id
```

**Lý do hybrid:** Nội dung/SEO quản lý qua WordPress native; booking performance qua custom SQL có index.

### Các file quan trọng

| File | Vai trò |
|---|---|
| `app/Controllers/AdminWorkshopController.php` | Toàn bộ Admin UI, form CRUD, AJAX handlers |
| `app/Controllers/MainController.php` | AJAX booking từ frontend (`bacera_book_workshop`) |
| `app/Database/WorkshopTables.php` | Schema DB, tự động migrate khi lên version |
| `single-workshop.php` | Trang chi tiết Workshop (booking UI) |
| `archive-workshop.php` | Trang danh sách Workshop |
| `app/Views/components/workshop-card.php` | Component card Workshop |

---

## 2. Cơ sở dữ liệu

### `bacera_workshop_slots` — Ca học

| Cột | Kiểu | Mô tả |
|---|---|---|
| `id` | mediumint | PK |
| `workshop_id` | mediumint | WordPress Post ID |
| `slot_date` | date | Ngày học |
| `time_start` | varchar(10) | Giờ bắt đầu (HH:MM) |
| `time_end` | varchar(10) | Giờ kết thúc (HH:MM) |
| `price` | varchar(100) | Giá riêng cho ca (override giá workshop) |
| `reg_start` | datetime | Thời điểm MỞ đăng ký |
| `reg_end` | datetime | Thời điểm ĐÓNG đăng ký |
| `total_seats` | tinyint | Tổng số ghế |
| `booked_seats` | tinyint | **Cache** — ghế đã đặt (sync từ bookings) |
| `status` | varchar | `open` / `full` / `cancelled` |

> [!WARNING]
> `booked_seats` là **cache field** — giá trị thực luôn được tính lại từ `SUM(num_seats)` trong bảng bookings. Đừng dùng cột này để kiểm tra availability chính xác.

### `bacera_workshop_bookings` — Đặt chỗ

| Cột | Kiểu | Mô tả |
|---|---|---|
| `id` | mediumint | PK |
| `slot_id` | mediumint | FK → slots |
| `workshop_id` | mediumint | FK → WordPress Post |
| `customer_id` | mediumint | FK → bacera_customers |
| `customer_name` | varchar | Tên lúc đặt |
| `phone` | varchar | SĐT lúc đặt |
| `seats_selected` | varchar | VD: `"1,2,3"` — ghế đã chọn |
| `num_seats` | tinyint | Số ghế đặt |
| `status` | varchar | `pending` / `confirmed` / `cancelled` |
| `payment_status` | varchar | `unpaid` / `deposited` / `paid` |
| `payment_method` | varchar | `cod` / `bank_transfer` / ... |
| `promo_code` | varchar | Mã KM đã dùng |
| `checked_in` | tinyint(1) | 0/1 |
| `checked_in_at` | datetime | Thời điểm điểm danh |

> [!NOTE]
> **1 booking = nhiều ghế.** Một khách có thể tạo nhiều bookings cho cùng 1 slot.

---

## 3. Hướng dẫn Admin — Quản lý Workshop

### 3.1 Truy cập

```
WP Admin → Workshop (sidebar) → Workshop Hub
URL: wp-admin/admin.php?page=bacera-workshops
```

### 3.2 Tab Tổng quan — Cấu hình nội dung

**CỘT TRÁI:**
- Tên Workshop *(bắt buộc)*
- Tagline — hiển thị nổi bật trên trang đặt chỗ
- Mô tả giới thiệu — sub description
- Meta Description — SEO (≤160 ký tự)
- Nội dung chi tiết — TinyMCE
- Học viên nhận được / Điểm nổi bật — danh sách thêm/xóa

**SIDEBAR (sticky):**
- Trạng thái (Public / Bản nháp)
- Học phí + Thời lượng + Giảng viên
- Ảnh đại diện (16:9)
- Ảnh gallery (carousel trên trang chi tiết)

> [!TIP]
> Nút **"Lưu thay đổi"** có ở cả topbar sticky VÀ sidebar — không cần cuộn khi chỉnh nội dung dài.

---

## 4. Hướng dẫn Admin — Ca học (Slots)

### 4.1 Trạng thái Ca học

| Status | Ý nghĩa | Frontend |
|---|---|---|
| `open` | Mở đăng ký | Hiện, cho đặt |
| `full` | Hết chỗ | Hiện, không đặt được |
| `cancelled` | Đã hủy | Ẩn hoàn toàn |

### 4.2 Cửa sổ đăng ký (Registration Window)

- **Mở ngay:** Toggle "Mở đăng ký ngay" → học viên đặt được ngay khi slot public
- **Lên lịch:** Chọn ngày/giờ mở và đóng cụ thể

### 4.3 Số ghế

- `total_seats` — số ghế vật lý trong ca
- Hệ thống hiển thị sơ đồ ghế để khách chọn
- 1 booking có thể chọn nhiều ghế

> [!CAUTION]
> Xóa ca học sẽ **xóa vĩnh viễn tất cả bookings** của ca đó. Hãy chuyển status `cancelled` thay vì xóa.

---

## 5. Hướng dẫn Admin — Học viên & Điểm danh

### 5.1 Roster inline trong card Ca học

Click vào avatar stack để expand:
- Tên học viên (lấy từ `bacera_customers` hiện tại, không phải tên lúc đặt cũ)
- SĐT, badge Đã TT / Chưa TT
- Nút [✓] điểm danh — AJAX realtime

### 5.2 Thứ tự ưu tiên hiển thị tên

```
1. bacera_customers.name    ← tên profile mới nhất
2. bookings.customer_name   ← tên lúc đặt
3. phone                    ← nếu không có tên
4. email
5. "Khách"
```

### 5.3 Tab Học viên (toàn bộ Workshop)

Xem tất cả bookings, filter theo slot, đổi trạng thái payment/status.

---

## 6. Quy trình đặt chỗ (Frontend)

```
Trang Workshop → Chọn Ca học → Chọn ghế → Đăng nhập
    → Điền ghi chú + promo code + phương thức TT
    → Submit AJAX → Xác nhận thành công
```

### Validation khi đặt (real-time, chống race condition)

| Kiểm tra | Lỗi nếu sai |
|---|---|
| Slot tồn tại & chưa cancelled | "Lịch học không tồn tại" |
| Status ≠ full | "Ca học này đã hết chỗ" |
| Trong cửa sổ đăng ký | "Chưa mở / Đã quá hạn" |
| `SUM(num_seats)` thực tế đủ ghế | "Chỉ còn X chỗ trống" |
| Ghế chưa bị đặt | "Ghế số X vừa có người đặt" |

---

## 7. Mã khuyến mãi & Thanh toán

- **Thanh toán:** WP Admin → Bacera → Thanh toán
- **Promo codes:** WP Admin → Bacera → Mã khuyến mãi
  - Hỗ trợ: `percent` / `fixed`
  - Tự increment `used_count` khi dùng

---

## 8. Kiểm tra & Xử lý sự cố

### Script sync `booked_seats` (chạy khi desync)

```sql
UPDATE wp_bacera_workshop_slots s
SET s.booked_seats = COALESCE((
    SELECT SUM(b.num_seats)
    FROM wp_bacera_workshop_bookings b
    WHERE b.slot_id = s.id AND b.status != 'cancelled'
), 0),
s.status = CASE
    WHEN s.booked_seats >= s.total_seats THEN 'full'
    WHEN s.status = 'full' AND s.booked_seats < s.total_seats THEN 'open'
    ELSE s.status
END;
```

### Lỗi thường gặp

| Triệu chứng | Nguyên nhân | Fix |
|---|---|---|
| Tên hiện "Khách hàng" | `customers.name` = default cũ | JOIN query tự xử lý |
| Ca vẫn "Hết chỗ" sau hủy | Cache desync | Chạy script sync |
| Nút điểm danh không phản hồi | Nonce hết hạn (>12h) | Reload trang |
| Gallery không hiển thị | `_gallery` meta chưa lưu | Tab Tổng quan → thêm ảnh → Lưu |

---

## 9. Roadmap & Đề xuất nâng cấp

### 🟡 Ngắn hạn

| # | Tính năng | Độ ưu tiên |
|---|---|---|
| 9.1 | **Email xác nhận tự động** sau khi đặt thành công | ⭐⭐⭐ |
| 9.2 | **DB Trigger** sync `booked_seats` thay vì PHP update | ⭐⭐ |
| 9.3 | **Waitlist** — đăng ký chờ khi hết chỗ, notify khi có slot | ⭐⭐ |

### 🟠 Trung hạn

| # | Tính năng | Độ ưu tiên |
|---|---|---|
| 9.4 | **REST API** — expose slots/booking endpoint | ⭐⭐ |
| 9.5 | **QR Code check-in** — scan thay vì tìm tên | ⭐⭐ |
| 9.6 | **Recurring slots** — tạo slot lặp lại theo lịch | ⭐⭐⭐ |

### 🔵 Dài hạn

| # | Tính năng | Ghi chú |
|---|---|---|
| 9.7 | **Rating tự động** — email mời review sau 48h | Token one-time, không cần login |
| 9.8 | **Analytics dashboard** — doanh thu, fill rate, trend | Tab mới trong Workshop Hub |
| 9.9 | **Multi-instructor** — assign nhiều giảng viên/slot | Cần thêm bảng `slot_instructors` |

---

## Phụ lục: CSS Design Tokens

```css
/* Admin UI variables */
--accent:    #ef4444;   /* Màu chủ đạo */
--green:     #10b981;   /* Thành công */
--amber:     #f59e0b;   /* Cảnh báo */
--red:       #ef4444;   /* Nguy hiểm */
--text-3:    #9ca3af;   /* Hint text */
--border:    #e5e7eb;
--surface:   #ffffff;
--surface-2: #f9fafb;
--r:         10px;      /* Border radius */
--rl:        14px;      /* Border radius lớn */
```

---

*Tài liệu cập nhật cùng source code. Lần cuối: 04/2026.*
