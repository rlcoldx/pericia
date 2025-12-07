# Configuração Nginx para o Projeto Perícia

## Problema
O projeto usa `.htaccess` (Apache), mas o servidor está usando **nginx**. O nginx não processa arquivos `.htaccess`, então é necessário configurar o nginx diretamente.

## Solução

### 1. Localizar o arquivo de configuração do nginx

O arquivo de configuração geralmente está em:
- `/etc/nginx/sites-available/` (Ubuntu/Debian)
- `/etc/nginx/conf.d/` (CentOS/RHEL)
- Ou dentro do bloco `http {}` no arquivo principal `/etc/nginx/nginx.conf`

### 2. Criar/Editar configuração do site

Crie um arquivo de configuração (ex: `/etc/nginx/sites-available/pericia`) com o conteúdo do arquivo `nginx.conf` fornecido, ajustando:

- **server_name**: Seu domínio
- **root**: Caminho completo do projeto no servidor
- **fastcgi_pass**: Socket ou porta do PHP-FPM (verifique com `ps aux | grep php-fpm` ou `systemctl status php-fpm`)

### 3. Exemplo de configuração ajustada:

```nginx
server {
    listen 80;
    server_name pericia.seudominio.com.br;
    root /var/www/pericia;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?route=$uri&$args;
    }

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;  # Ajuste a versão do PHP
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        
        fastcgi_param PHP_VALUE "upload_max_filesize=150M \n post_max_size=150M \n max_execution_time=300 \n max_input_time=300 \n memory_limit=256M";
    }

    # Bloquear acesso a arquivos sensíveis
    location ~ /\. {
        deny all;
    }

    location ~ ^/(config|vendor|application|routes|constants\.php|db\.php|functions\.php) {
        deny all;
    }
}
```

### 4. Habilitar o site (Ubuntu/Debian)

```bash
sudo ln -s /etc/nginx/sites-available/pericia /etc/nginx/sites-enabled/
```

### 5. Testar e recarregar nginx

```bash
# Testar configuração
sudo nginx -t

# Se estiver OK, recarregar
sudo systemctl reload nginx
# ou
sudo service nginx reload
```

### 6. Verificar PHP-FPM

Se ainda não funcionar, verifique se o PHP-FPM está rodando:

```bash
sudo systemctl status php-fpm
# ou
sudo systemctl status php7.4-fpm  # Ajuste a versão
```

E verifique o socket/porta do PHP-FPM no arquivo de configuração do PHP-FPM:
- `/etc/php/7.4/fpm/pool.d/www.conf` (procure por `listen =`)

### 7. Permissões

Certifique-se de que o nginx tem permissão para ler os arquivos:

```bash
sudo chown -R www-data:www-data /var/www/pericia
sudo chmod -R 755 /var/www/pericia
```

### 8. Logs para debug

Se ainda houver problemas, verifique os logs:

```bash
sudo tail -f /var/log/nginx/pericia_error.log
sudo tail -f /var/log/nginx/error.log
```

## Diferenças principais entre Apache e Nginx

- **Apache**: Usa `.htaccess` para configurações por diretório
- **Nginx**: Configuração centralizada no arquivo de configuração do servidor
- **Rewrite**: Apache usa `RewriteRule`, Nginx usa `try_files` e `location`

## Comandos úteis

```bash
# Verificar status do nginx
sudo systemctl status nginx

# Reiniciar nginx
sudo systemctl restart nginx

# Ver processos PHP-FPM
ps aux | grep php-fpm

# Verificar porta/socket do PHP-FPM
sudo netstat -tlnp | grep php-fpm
```
