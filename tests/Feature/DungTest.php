<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;
use App\Category;
use App\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DungTest extends TestCase
{
    use RefreshDatabase;
   /**
     * Hàm setUp được gọi trước mỗi test để thiết lập môi trường kiểm thử ban đầu.
     * Ở đây chúng ta tạo dữ liệu thủ công cho Category, Tag và Product, và gắn các Tag vào Product.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Tạo dữ liệu thủ công
        // Tạo Categories
        $category1 = Category::create(['slug' => 'category-1', 'name' => 'Category 1']);
        $category2 = Category::create(['slug' => 'category-2', 'name' => 'Category 2']);
        
        // Tạo Tags
        $tag1 = Tag::create(['slug' => 'tag-1', 'name' => 'Tag 1']);
        $tag2 = Tag::create(['slug' => 'tag-2', 'name' => 'Tag 2']);
        
        // Tạo Products
        $product1 = Product::create(['category_id' => $category1->id, 'name' => 'abc', 'slug' => '123a', 'image' => 'xyz', 'description' => 'abc', 'price' => 100, 'featured' => true]);
        $product2 = Product::create(['category_id' => $category1->id, 'name' => 'abc1', 'slug' => '123b', 'image' => 'xyz', 'description' => 'abc', 'price' => 200, 'featured' => true]);
        $product3 = Product::create(['category_id' => $category2->id, 'name' => 'abc2', 'slug' => '123c', 'image' => 'xyz', 'description' => 'abc', 'price' => 150, 'featured' => false]);
        $product4 = Product::create(['category_id' => $category2->id, 'name' => 'abc3', 'slug' => '123d', 'image' => 'xyz', 'description' => 'abc', 'price' => 50, 'featured' => true]);
        
        // Gắn Tags cho Products (nếu cần)
        $tag1->products()->attach([$product1->id, $product2->id]);
        $tag2->products()->attach([$product3->id, $product4->id]);
    }

    /**
     * Kiểm tra nếu không có Category hoặc Tag nào được cung cấp, thì sẽ trả về các sản phẩm có 'featured'
     */
    /** @test */
    public function it_returns_featured_products_when_no_category_or_tag_is_provided()
    {
        $response = $this->get(route('shop.index')); // Giả sử route là shop.index

        $response->assertStatus(200); // Kiểm tra trạng thái phản hồi là 200 (OK)
        $response->assertViewHas('products'); // Kiểm tra view có chứa 'products'
        $this->assertCount(3, $response->viewData('products')); // Kiểm tra số lượng sản phẩm 'featured' là 3
    }

    /**
     * Kiểm tra nếu có Category được cung cấp, thì sẽ trả về các sản phẩm thuộc Category đó
     */
    /** @test */
    public function it_returns_products_filtered_by_category()
    {
        $category = Category::where('slug', 'category-1')->first(); // Lấy Category có slug 'category-1'
        
        $response = $this->get(route('shop.index', ['category' => $category->slug])); // Gọi route với category 'category-1'

        $response->assertStatus(200); // Kiểm tra trạng thái phản hồi là 200 (OK)
        $response->assertViewHas('products'); // Kiểm tra view có chứa 'products'
        $this->assertCount(2, $response->viewData('products')); // Kiểm tra số lượng sản phẩm trong category-1 là 2
        $this->assertEquals('Category 1', $response->viewData('categoryName')); // Kiểm tra tên của category là 'Category 1'
    }

    /**
     * Kiểm tra nếu có Tag được cung cấp, thì sẽ trả về các sản phẩm có gắn Tag đó
     */
    /** @test */
    public function it_returns_products_filtered_by_tag()
    {
        $tag = Tag::where('slug', 'tag-1')->first(); // Lấy Tag có slug 'tag-1'
        
        $response = $this->get(route('shop.index', ['tag' => $tag->slug])); // Gọi route với tag 'tag-1'

        $response->assertStatus(200); // Kiểm tra trạng thái phản hồi là 200 (OK)
        $response->assertViewHas('products'); // Kiểm tra view có chứa 'products'
        $this->assertCount(2, $response->viewData('products')); // Kiểm tra số lượng sản phẩm có gắn tag-1 là 2
        $this->assertEquals('Tag 1', $response->viewData('tagName')); // Kiểm tra tên của tag là 'Tag 1'
    }

    /**
     * Kiểm tra nếu sắp xếp sản phẩm từ giá thấp đến cao
     */
    /** @test */
    public function it_sorts_products_low_to_high()
    {
        $response = $this->get(route('shop.index', ['sort' => 'low_high'])); // Gọi route với sắp xếp giá từ thấp đến cao

        $response->assertStatus(200); // Kiểm tra trạng thái phản hồi là 200 (OK)
        $response->assertViewHas('products'); // Kiểm tra view có chứa 'products'
        $products = $response->viewData('products'); // Lấy danh sách sản phẩm từ view
        $this->assertTrue($products->first()->price <= $products->last()->price); // Kiểm tra giá của sản phẩm đầu tiên nhỏ hơn hoặc bằng giá sản phẩm cuối cùng
    }

    /**
     * Kiểm tra nếu sắp xếp sản phẩm từ giá cao đến thấp
     */
    /** @test */
    public function it_sorts_products_high_to_low()
    {
        $response = $this->get(route('shop.index', ['sort' => 'high_low'])); // Gọi route với sắp xếp giá từ cao xuống thấp

        $response->assertStatus(200); // Kiểm tra trạng thái phản hồi là 200 (OK)
        $response->assertViewHas('products'); // Kiểm tra view có chứa 'products'
        $products = $response->viewData('products'); // Lấy danh sách sản phẩm từ view
        $this->assertTrue($products->first()->price >= $products->last()->price); // Kiểm tra giá của sản phẩm đầu tiên lớn hơn hoặc bằng giá sản phẩm cuối cùng
    }

    /**
     * Kiểm tra nếu sản phẩm được trả về với thứ tự ngẫu nhiên
     */
    /** @test */
    public function it_returns_products_in_random_order()
    {
        $response = $this->get(route('shop.index', ['sort' => 'random'])); // Gọi route với sắp xếp ngẫu nhiên

        $response->assertStatus(200); // Kiểm tra trạng thái phản hồi là 200 (OK)
        $response->assertViewHas('products'); // Kiểm tra view có chứa 'products'
        $products = $response->viewData('products'); // Lấy danh sách sản phẩm từ view
        $this->assertNotEquals($products->first()->id, $products->last()->id); // Kiểm tra rằng sản phẩm đầu tiên khác sản phẩm cuối cùng để đảm bảo sắp xếp ngẫu nhiên
    }

    /**
     * Kiểm tra nếu trả về đầy đủ các Categories và Tags
     */
    /** @test */
    public function it_returns_all_categories_and_tags()
    {
        $response = $this->get(route('shop.index')); // Gọi route shop.index

        $response->assertStatus(200); // Kiểm tra trạng thái phản hồi là 200 (OK)
        $response->assertViewHas('categories'); // Kiểm tra view có chứa 'categories'
        $response->assertViewHas('tags'); // Kiểm tra view có chứa 'tags'
        $this->assertCount(2, $response->viewData('categories')); // Kiểm tra số lượng categories là 2
        $this->assertCount(2, $response->viewData('tags')); // Kiểm tra số lượng tags là 2
    }
}