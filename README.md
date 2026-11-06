# RestaurantPro

RestaurantPro is a beginner-friendly PHP 8 and MySQL restaurant ordering and management project for XAMPP. It uses plain PHP pages, HTML forms, CSS, a little JavaScript and PDO; it does not use a framework or external library.

## Run locally with XAMPP

1. Put the project folder in `C:\xampp\htdocs\RestaurantPro`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin`.
4. Choose the **Import** tab and import `database/restaurantpro.sql`. The SQL creates only missing tables and adds sample menu rows only if they are not already present. It does not drop tables or delete existing data.
5. If your MySQL account is not the default XAMPP `root` account with an empty password, update the connection settings in `config/database.php`.
6. Open `http://localhost/RestaurantPro/`.
7. Create the first administrator at `http://localhost/RestaurantPro/setup_admin.php`. This page closes itself after an administrator has been created. Use a password you choose; the project does not ship with a default staff password.
8. Log in and use **Staff accounts** in the administrator dashboard to create waiter and kitchen accounts. Customers can use **Register**.

## Included modules

- Homepage, customer registration, login and logout.
- First administrator setup and role-based dashboards for administrators, waiters, kitchen staff and customers.
- Category and menu item create, view, edit and delete; menu image uploads accept JPEG, PNG or WebP up to 2 MB.
- Customer records, waiter customer registration and staff account activation.
- Waiter order creation, customer cart and checkout, customer order history and a kitchen order board.
- Cash payment recording, printable receipts, daily/monthly paid sales and popular food reports.

The payment screens record cash collected by restaurant staff. They do not connect to a real card processor or mobile-money provider.

## Project layout

- `admin/`, `waiter/`, `kitchen/`, `customer/`: pages for each role.
- `config/database.php`: PDO connection. Include it on pages that query MySQL.
- `includes/`: shared navigation, layout, role checks and form helpers.
- `assets/css/style.css`, `assets/js/script.js`: the shared site styling and small navigation/confirmation interactions.
- `database/restaurantpro.sql`: table definitions and example menu data.
- `uploads/menu/`: uploaded food pictures.
- `index.php`, `register.php`, `login.php`, `logout.php`, `receipt.php`: public and shared pages.

## Learning and testing checklist

1. Visit the homepage and check the sample menu.
2. Register a customer, log in, add food to the cart, change a quantity and place an order.
3. Log in as an administrator; add a category and food item, edit each, test availability and remove a test item.
4. Add a waiter and kitchen staff account. Register a walk-in customer and create an order as the waiter.
5. Open the kitchen order board and move the order through **Preparing** and **Ready**.
6. Record the cash payment as the waiter or administrator. Confirm that the receipt shows the payment and the reports update.
7. Confirm that a customer can see their own order, but cannot open another customer's receipt by changing its ID in the URL.
8. Try opening each dashboard while logged out and while logged in as a different role; access should be denied or redirected.

## Basic security used in the project

- Database writes use PDO prepared statements.
- Passwords use `password_hash()` and `password_verify()`.
- Forms that change data use session-based CSRF tokens.
- Pages check the signed-in user's role; customer order details are restricted to that customer.
- Displayed database values use `htmlspecialchars()` through the small `e()` helper.
- Uploaded images are size/type checked and given random filenames. The upload directory blocks PHP scripts.

This is a local academic project, not a production-ready public ordering service. Before deploying publicly, configure proper database credentials, HTTPS, backups, server error handling and hosting security.
