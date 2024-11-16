<?php

namespace Tests\Feature;


use App\Http\Controllers\LoginController;
use App\User;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;


class GiapTest extends TestCase
{

    // Controller : Auth / LoginController ( chuc nang dang nhap )
    //  Sử dụng trait RefreshDatabase để reset lại database trước mỗi bài test, giúp đảm bảo môi trường dữ liệu luôn sạch sẽ và không ảnh hưởng giữa các bài test
    use RefreshDatabase;

    /**
     * Test khi người dùng không có email và tìm user bằng provider_id.
     */
    public function test_handleProviderCallback_noEmail()
    {
        // Tạo mock cho provider user
        // Tạo một mock của ProviderUser để giả lập thông tin người dùng từ provider. Trong trường hợp này, getEmail sẽ trả về null (người dùng không có email), và getId trả về '12345' làm id của provider.
        $providerUserMock = Mockery::mock(ProviderUser::class);
        $providerUserMock->shouldReceive('getEmail')->andReturn(null);
        $providerUserMock->shouldReceive('getId')->andReturn('12345');

        //  Mock Socialite để giả lập quá trình xác thực với provider, trả về thông tin providerUserMock khi gọi đến driver->user().
        Socialite::shouldReceive('driver->user')->andReturn($providerUserMock);

        // Tạo trực tiếp một đối tượng User và lưu vào database với các thông tin cơ bản, bao gồm email, name, và github_id. 
        // Điều này giả lập người dùng đã tồn tại trong hệ thống với github_id là 12345
        $user = new User([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'github_id' => '12345',
        ]);
        $user->save();

        // Gọi route /login/github/callback để kiểm tra quy trình xử lý khi provider callback về, nơi handleProviderCallback() sẽ được gọi.
        $response = $this->get('/login/github/callback');

        // Kiểm tra xem người dùng $user đã được đăng nhập thành công hay chưa
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test khi người dùng có email nhưng chưa có provider ID.
     */
    public function test_handleProviderCallback_hasEmail_noProviderId()
    {
        //  Tạo mock của ProviderUser với email 'test@example.com', tên 'Test User', và ID của provider là '12345'.
        //  Trường hợp này giả lập người dùng có email nhưng chưa có github_id.
        $providerUserMock = Mockery::mock(ProviderUser::class);
        $providerUserMock->shouldReceive('getEmail')->andReturn('test@example.com');
        $providerUserMock->shouldReceive('getName')->andReturn('Test User');
        $providerUserMock->shouldReceive('getId')->andReturn('12345');

        // Mock Socialite để trả về thông tin từ providerUserMock khi gọi đến driver->user().
        Socialite::shouldReceive('driver->user')->andReturn($providerUserMock);

        // Tạo một đối tượng User trong database với github_id là null (giả lập người dùng chưa kết nối tài khoản GitHub).
        $user = new User([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'github_id' => null,
        ]);
        $user->save();

        // Gọi route /login/github/callback để kiểm tra xử lý khi có callback từ provider.
        $response = $this->get('/login/github/callback');

        //  Kiểm tra trong database có bản ghi với email là 'test@example.com' và github_id vẫn null, xác nhận ID provider chưa bị cập nhật nếu điều kiện không thỏa mãn.
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'github_id' => null,
        ]);

        // Kiểm tra xem người dùng $user đã được đăng nhập thành công.
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test khi người dùng chưa tồn tại trong hệ thống.
     */
    public function test_handleProviderCallback_userDoesNotExist()
    {
        // Tạo một mock cho ProviderUser để giả lập trường hợp người dùng mới chưa tồn tại trong hệ thống. 
        // Mock này trả về email 'newuser@example.com', tên 'New User', và id của provider là '67890'
        $providerUserMock = Mockery::mock(ProviderUser::class);
        $providerUserMock->shouldReceive('getEmail')->andReturn('newuser@example.com');
        $providerUserMock->shouldReceive('getName')->andReturn('New User');
        $providerUserMock->shouldReceive('getId')->andReturn('67890');

        // Mock Socialite để trả về providerUserMock khi gọi driver->user(), giả lập dữ liệu trả về từ provider.
        Socialite::shouldReceive('driver->user')->andReturn($providerUserMock);

        // Gọi route /login/github/callback để kiểm tra xử lý khi có callback từ provider cho người dùng mới.
        $response = $this->get('/login/github/callback');

        // Kiểm tra trong database đã có bản ghi của người dùng mới với thông tin email, tên, và github_id tương ứng, xác nhận user mới đã được tạo.
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'New User',
            'github_id' => '67890',
        ]);

        // Tìm người dùng vừa được tạo trong database và kiểm tra xem họ đã được đăng nhập thành công.
        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertAuthenticatedAs($user);
    }


}
