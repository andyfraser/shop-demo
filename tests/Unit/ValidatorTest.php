<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Core\Validator;

class ValidatorTest extends TestCase {
    private Validator $validator;

    public function setUp() {
        $this->validator = new Validator();
    }

    public function testRequiredRule() {
        $data = ['name' => ''];
        $rules = ['name' => 'required'];
        $errors = $this->validator->check($data, $rules);
        $this->assertCount(1, $errors);
        $this->assertEquals("Name is required.", $errors[0]);

        $data = ['name' => 'John'];
        $errors = $this->validator->check($data, $rules);
        $this->assertCount(0, $errors);
    }

    public function testEmailRule() {
        $rules = ['email' => 'email'];
        
        $data = ['email' => 'invalid-email'];
        $errors = $this->validator->check($data, $rules);
        $this->assertCount(1, $errors);
        $this->assertEquals("Valid email required.", $errors[0]);

        $data = ['email' => 'test@example.com'];
        $errors = $this->validator->check($data, $rules);
        $this->assertCount(0, $errors);
    }

    public function testMinLengthRule() {
        $rules = ['password' => 'min_length:6'];
        
        $data = ['password' => '12345'];
        $errors = $this->validator->check($data, $rules);
        $this->assertCount(1, $errors);
        $this->assertEquals("Password must be at least 6 characters.", $errors[0]);

        $data = ['password' => '123456'];
        $errors = $this->validator->check($data, $rules);
        $this->assertCount(0, $errors);
    }

    public function testMaxLengthRule() {
        $rules = ['username' => 'max_length:5'];
        
        $data = ['username' => 'abcdef'];
        $errors = $this->validator->check($data, $rules);
        $this->assertCount(1, $errors);
        $this->assertEquals("Username must be no more than 5 characters.", $errors[0]);

        $data = ['username' => 'abcde'];
        $errors = $this->validator->check($data, $rules);
        $this->assertCount(0, $errors);
    }

    public function testPositiveRule() {
        $rules = ['price' => 'positive'];
        
        $data = ['price' => '0'];
        $errors = $this->validator->check($data, $rules);
        $this->assertCount(1, $errors);
        $this->assertEquals("Price must be positive.", $errors[0]);

        $data = ['price' => '-5'];
        $errors = $this->validator->check($data, $rules);
        $this->assertCount(1, $errors);

        $data = ['price' => '10.5'];
        $errors = $this->validator->check($data, $rules);
        $this->assertCount(0, $errors);
    }

    public function testMinRule() {
        $rules = ['age' => 'min:18'];
        
        $data = ['age' => '17'];
        $errors = $this->validator->check($data, $rules);
        $this->assertCount(1, $errors);
        $this->assertEquals("Age must be at least 18.", $errors[0]);

        $data = ['age' => '18'];
        $errors = $this->validator->check($data, $rules);
        $this->assertCount(0, $errors);
    }

    public function testInRule() {
        $rules = ['role' => 'in:admin,customer'];
        
        $data = ['role' => 'guest'];
        $errors = $this->validator->check($data, $rules);
        $this->assertCount(1, $errors);
        $this->assertEquals("Invalid Role.", $errors[0]);

        $data = ['role' => 'admin'];
        $errors = $this->validator->check($data, $rules);
        $this->assertCount(0, $errors);
    }

    public function testMultipleRules() {
        $rules = ['email' => 'required|email'];
        
        $data = ['email' => ''];
        $errors = $this->validator->check($data, $rules);
        $this->assertCount(1, $errors);
        $this->assertEquals("Email is required.", $errors[0]);

        $data = ['email' => 'invalid'];
        $errors = $this->validator->check($data, $rules);
        $this->assertCount(1, $errors);
        $this->assertEquals("Valid email required.", $errors[0]);

        $data = ['email' => 'test@example.com'];
        $errors = $this->validator->check($data, $rules);
        $this->assertCount(0, $errors);
    }

    public function testArrayInputHandling() {
        $rules = [
            'name' => 'required',
            'email' => 'required|email'
        ];

        // Pass array instead of string for name (e.g. name[]=foo)
        $data = [
            'name' => ['first', 'second'],
            'email' => ['not', 'a', 'scalar']
        ];

        // Should not throw ErrorException (Array to string conversion)
        $errors = $this->validator->check($data, $rules);
        
        $this->assertTrue(count($errors) > 0);
        $this->assertTrue(in_array("Invalid Email format.", $errors, true));
    }

    public function testFieldKeyedErrors() {
        $rules = [
            'name' => 'required',
            'email' => 'required|email'
        ];

        $data = [
            'name' => '',
            'email' => 'invalid-email'
        ];

        $errors = $this->validator->check($data, $rules);
        
        $this->assertTrue($this->validator->hasErrors());
        $this->assertEquals("Name is required.", $this->validator->getFieldError('name'));
        $this->assertEquals("Valid email required.", $this->validator->getFieldError('email'));
        
        $errorsForEmail = $this->validator->getFieldErrors('email');
        $this->assertCount(1, $errorsForEmail);
        $this->assertEquals("Valid email required.", $errorsForEmail[0]);
    }
}
