<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Core\Validator;

class ValidatorTest extends TestCase {
    public function testRequiredRule() {
        $data = ['name' => ''];
        $rules = ['name' => 'required'];
        $errors = Validator::check($data, $rules);
        $this->assertCount(1, $errors);
        $this->assertEquals("Name is required.", $errors[0]);

        $data = ['name' => 'John'];
        $errors = Validator::check($data, $rules);
        $this->assertCount(0, $errors);
    }

    public function testEmailRule() {
        $rules = ['email' => 'email'];
        
        $data = ['email' => 'invalid-email'];
        $errors = Validator::check($data, $rules);
        $this->assertCount(1, $errors);
        $this->assertEquals("Valid email required.", $errors[0]);

        $data = ['email' => 'test@example.com'];
        $errors = Validator::check($data, $rules);
        $this->assertCount(0, $errors);
    }

    public function testMinLengthRule() {
        $rules = ['password' => 'min_length:6'];
        
        $data = ['password' => '12345'];
        $errors = Validator::check($data, $rules);
        $this->assertCount(1, $errors);
        $this->assertEquals("Password must be at least 6 characters.", $errors[0]);

        $data = ['password' => '123456'];
        $errors = Validator::check($data, $rules);
        $this->assertCount(0, $errors);
    }

    public function testMaxLengthRule() {
        $rules = ['username' => 'max_length:5'];
        
        $data = ['username' => 'abcdef'];
        $errors = Validator::check($data, $rules);
        $this->assertCount(1, $errors);
        $this->assertEquals("Username must be no more than 5 characters.", $errors[0]);

        $data = ['username' => 'abcde'];
        $errors = Validator::check($data, $rules);
        $this->assertCount(0, $errors);
    }

    public function testPositiveRule() {
        $rules = ['price' => 'positive'];
        
        $data = ['price' => '0'];
        $errors = Validator::check($data, $rules);
        $this->assertCount(1, $errors);
        $this->assertEquals("Price must be positive.", $errors[0]);

        $data = ['price' => '-5'];
        $errors = Validator::check($data, $rules);
        $this->assertCount(1, $errors);

        $data = ['price' => '10.5'];
        $errors = Validator::check($data, $rules);
        $this->assertCount(0, $errors);
    }

    public function testMinRule() {
        $rules = ['age' => 'min:18'];
        
        $data = ['age' => '17'];
        $errors = Validator::check($data, $rules);
        $this->assertCount(1, $errors);
        $this->assertEquals("Age must be at least 18.", $errors[0]);

        $data = ['age' => '18'];
        $errors = Validator::check($data, $rules);
        $this->assertCount(0, $errors);
    }

    public function testInRule() {
        $rules = ['role' => 'in:admin,customer'];
        
        $data = ['role' => 'guest'];
        $errors = Validator::check($data, $rules);
        $this->assertCount(1, $errors);
        $this->assertEquals("Invalid Role.", $errors[0]);

        $data = ['role' => 'admin'];
        $errors = Validator::check($data, $rules);
        $this->assertCount(0, $errors);
    }

    public function testMultipleRules() {
        $rules = ['email' => 'required|email'];
        
        $data = ['email' => ''];
        $errors = Validator::check($data, $rules);
        $this->assertCount(1, $errors);
        $this->assertEquals("Email is required.", $errors[0]);

        $data = ['email' => 'invalid'];
        $errors = Validator::check($data, $rules);
        $this->assertCount(1, $errors);
        $this->assertEquals("Valid email required.", $errors[0]);

        $data = ['email' => 'test@example.com'];
        $errors = Validator::check($data, $rules);
        $this->assertCount(0, $errors);
    }
}
