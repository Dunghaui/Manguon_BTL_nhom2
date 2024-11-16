<?php

namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;
use Illuminate\Http\Request;
use App\Coupon;
use App\Http\Controllers\CouponsController;
use Cart;
class AnhTest extends TestCase
{
    // Controller: Coupons Controller 
    // Mã của CouponsController
    // Trong controller này, có hai phương thức chính:

    // store: Áp dụng mã giảm giá từ client gửi lên.
    // destroy: Xóa mã giảm giá khỏi session.

    // Mục tiêu kiểm thử:
    // Kiểm thử hộp trắng để bao phủ các quyết định trong mã.
    // Đảm bảo rằng các nhánh (branches) của điều kiện được kiểm tra.
    // Kỹ thuật bao phủ quyết định (Decision Coverage)
    // Bao phủ quyết định có nghĩa là chúng ta cần kiểm tra tất cả các biểu thức điều kiện (như if và else) trong mã, với các giá trị true và false.
    // Mỗi điều kiện phải được kiểm tra ít nhất một lần khi có thể xảy ra tình huống điều kiện đúng và sai.

    // Unit Test cho phương thức store
    // 1. Trường hợp mã giảm giá không hợp lệ (Invalid Coupon Code)
    // Khi người dùng nhập mã giảm giá không hợp lệ, hệ thống phải trả về thông báo lỗi.

    public function testStoreWithInvalidCouponCode()
    {
        // Sử dụng Mockery để tạo một đối tượng giả (mock) từ lớp Coupon. 
        // Đối tượng giả này sẽ được sử dụng để giả lập các phương thức của Coupon, nhằm kiểm soát đầu ra khi test mà không cần truy cập vào cơ sở dữ liệu thật.
        $couponQueryMock = Mockery::mock(Coupon::class);

        // Đặt kỳ vọng rằng phương thức where của đối tượng Coupon sẽ không bao giờ (never) được gọi trong quá trình test.
        // andReturnSelf() được thêm vào để đảm bảo nếu where được gọi (trong trường hợp không mong muốn), nó sẽ trả về chính đối tượng Coupon.
        $couponQueryMock->shouldReceive('where')
            ->never()
            ->andReturnSelf();

        // Đặt kỳ vọng rằng phương thức first của đối tượng Coupon cũng không bao giờ (never) được gọi.
        // andReturn(null) chỉ ra rằng nếu first được gọi
        $couponQueryMock->shouldReceive('first')
            ->never()
            ->andReturn(null);

        // Tạo một đối tượng Request mới chứa dữ liệu đầu vào có mã coupon là 'INVALIDCODE'. 
        // Đối tượng này sẽ giả lập yêu cầu HTTP của người dùng khi họ nhập mã coupon không hợp lệ.
        $request = new Request(['coupon_code' => 'INVALIDCODE']);

        // Tạo một đối tượng CouponsController. Đây là đối tượng sẽ được kiểm tra bằng cách gọi phương thức store trên nó.
        $controller = new CouponsController();
        
        // Gọi phương thức store của CouponsController với đối tượng Request vừa tạo. Kết quả của phương thức store sẽ được lưu trong biến $response.
        $response = $controller->store($request);

        // Kiểm tra xem URL đích của $response có bằng với route của checkout.index hay không. 
        // Điều này có nghĩa là khi mã coupon không hợp lệ, người dùng sẽ được chuyển hướng tới trang checkout.index
        $this->assertEquals(route('checkout.index'), $response->getTargetUrl());

        // Kiểm tra xem thông báo lỗi trong session có phải là 'Invalid Coupon Code' hay không. 
        // Điều này đảm bảo rằng thông báo lỗi thích hợp sẽ được hiển thị cho người dùng khi họ nhập mã coupon không hợp lệ.
        $this->assertEquals('Invalid Coupon Code', session('error'));
    }

    // Khi người dùng nhập mã giảm giá hợp lệ, hệ thống phải lưu mã giảm giá vào session và hiển thị thông báo thành công.
    // Giải thích:
    // Bao phủ quyết định:
    // Điều kiện if (!$coupon) sẽ không được kích hoạt trong trường hợp này vì coupon hợp lệ được tìm thấy trong cơ sở dữ liệu. Nhánh false của quyết định if (!$coupon) sẽ không được kiểm tra.
    // Nếu coupon hợp lệ, hệ thống sẽ tiếp tục lưu coupon vào session và trả về thông báo thành công. Các câu lệnh này đều được kiểm tra.
    // Kiểm thử với mã coupon hợp lệ
    public function testStoreWithValidCouponCode()
    {
        // Mock đối tượng Coupon
        $coupon = Mockery::mock(Coupon::class);
        
        // Giả lập hành vi của phương thức where và first
        $coupon->shouldReceive('where')
            ->never();

        // Giả lập phương thức first() trả về đối tượng Coupon mock
        $coupon->shouldReceive('first')
            ->never();

        // Mock phương thức Cart::instance() và Cart::subtotal()
        Cart::shouldReceive('instance')
            ->once()
            ->with('default')
            ->andReturnSelf();  // Trả về chính đối tượng Cart mock

        Cart::shouldReceive('subtotal')
            ->once()
            ->andReturn(1000);  // Giả lập subtotal là 1000

        // Tạo request giả với coupon code
        $request = new Request(['coupon_code' => 'DEF456']);
        $controller = new CouponsController();

        // Gọi phương thức store của controller
        $response = $controller->store($request);

        // Kiểm tra xem có chuyển hướng đúng và thông báo thành công không
        $this->assertEquals(route('checkout.index'), $response->getTargetUrl());
        $this->assertEquals('Coupon applied successfully!', session('success')); 

    }


}
