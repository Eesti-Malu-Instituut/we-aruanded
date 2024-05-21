# Eesti Mälu Instituudi aruanded

See on Eesti Mälu Instituudi andmebaasi statistika repositoorium.

## Nõuded süsteemile

- PHP versioon 8.0 või hilisem
    `apt install php libapache2-mod-php`
- ssh2 for PHP
    `apt install php-ssh2`


## Installeerimine

```sh
# Navigate to the project's directory
cd /var/www

# Clone the repository
git clone git@github.com:Eesti-Malu-Instituut/we-aruanded.git
# Kui ssh ühendust ei soovi tekitada, siis saab ka
git clone https://github.com/Eesti-Malu-Instituut/we-aruanded.git html

# Generate SSH key for ssh2_connect
sudo mkdir /var/www/.ssh
sudo chown -R www-data /var/www/.ssh
sudo -u www-data ssh-keygen -t rsa

# Copy project environment file
cp -n .env.example .env

# Fill project environment file with correct data
vim .env
:x

# copy files to web directory
# (local directory would be /var/www/html)
cp -R -n * /var/www/html

# open your browser at `http://localhost:8000/index.php`

# filters can be added and mysql queries modified in filters.json file

# table rows can be modified in table_rows.json fileAA
```

## Local setup with DDEV

### Requirements
* DDEV

### setup
```sh
# Clone the repository
git clone git@github.com:Eesti-Malu-Instituut/we-aruanded.git

# Navigate to the project's directory
cd we-aruanded

# Start DDEV container
ddev start

# ssh into DDEV container
ddev ssh

# Generate SSH key for ssh2_connect
ssh-keygen -t rsa -m PEM

# Add SSH public key to server ssh user authorized_keys

# Copy project environment file
cp -n .env.example .env

# Fill project environment file with correct data
nano .env

# open your browser at `https://eesti-malu-instituut-aruanded.ddev.site`
```
