Syntax showcase

```olang

// this is a struct
struct user {
    username: string = "test"
    email: string    = "asd"
    phone: string    = "awerqw"
    
    // this is a struct callable (function), it returns bool
    is_admin => bool {
        // logic goes here
    }
}

// this is a callable, it takes 2 strings and returns a bool
validate => bool {
    email: string = "my@email.com"
    phone: string = "111111"

    // validation logic
    
}

// calling a callable, parameters must always be named
validate(email: "some@email.com", phone: "22222")

// this is an if expression
if 1 > 2 {
    // some comment
    return 1
}

// if else expression
if 2 > 1 {
    // then
} else {
    // else
}


// and nested
if 2 > 1 {
    // then
} else {
    // else
    if 3 === 3 {
    }
}

if 2 > 1 {
    // then
} else if 3 === 3 {
    // else if
}

// due to how the parser works, this is also allowed
if 2 > 1 {
    // then
} else => if 3 === 3 {
    // else if
}

// a few more interesting examples

if 1 > 2 => validate(email: "some.other@email.com", phone: "33333333")
else if 1 > 2 => false

// this looks weird, don't think many would use it, but it's allowed
if 1 > 2 => validate(email: "some.other.other@email.com", phone: "44444444")
else => if 1 > 2 => false
```
