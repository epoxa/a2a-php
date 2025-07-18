#!/bin/bash

# A2A Server HTTPS/TLS Setup Script
# This script demonstrates how to run the A2A server in both HTTP and HTTPS modes

echo "🔐 A2A Server HTTPS/TLS Setup"
echo "=============================="

# Function to check if port is available
check_port() {
    local port=$1
    if lsof -Pi :$port -sTCP:LISTEN -t >/dev/null 2>&1; then
        echo "❌ Port $port is already in use"
        return 1
    else
        echo "✅ Port $port is available"
        return 0
    fi
}

# Function to start HTTP server (development mode)
start_http_server() {
    echo
    echo "🌐 Starting HTTP Server (Development Mode)"
    echo "----------------------------------------"
    
    if check_port 8081; then
        echo "Starting A2A server on HTTP port 8081..."
        echo "Agent Card: http://localhost:8081/.well-known/agent.json"
        echo "Server Info: http://localhost:8081/server-info"
        echo
        echo "Press Ctrl+C to stop the server"
        echo
        php -S localhost:8081 https_a2a_server.php
    fi
}

# Function to start HTTPS server (production mode)
start_https_server() {
    echo
    echo "🔒 Starting HTTPS Server (Production Mode)"
    echo "------------------------------------------"
    
    if check_port 8443; then
        echo "Generating SSL certificates..."
        echo "Starting A2A server on HTTPS port 8443..."
        echo "Agent Card: https://localhost:8443/.well-known/agent.json"
        echo "Server Info: https://localhost:8443/server-info"
        echo
        echo "⚠️  Note: You may see SSL warnings for self-signed certificates"
        echo "   This is normal for development. Use proper certificates in production."
        echo
        echo "Press Ctrl+C to stop the server"
        echo
        A2A_MODE=production php -S localhost:8443 https_a2a_server.php
    fi
}

# Function to run tests on both servers
run_tests() {
    echo
    echo "🧪 Testing Both HTTP and HTTPS Servers"
    echo "======================================"
    
    # Test HTTP server
    echo "Testing HTTP server..."
    if curl -s http://localhost:8081/.well-known/agent.json > /dev/null 2>&1; then
        echo "✅ HTTP server is responding"
    else
        echo "❌ HTTP server is not responding"
    fi
    
    # Test HTTPS server (skip SSL verification for self-signed certs)
    echo "Testing HTTPS server..."
    if curl -k -s https://localhost:8443/.well-known/agent.json > /dev/null 2>&1; then
        echo "✅ HTTPS server is responding"
    else
        echo "❌ HTTPS server is not responding"
    fi
}

# Function to show server comparison
show_comparison() {
    echo
    echo "📊 HTTP vs HTTPS Comparison"
    echo "==========================="
    echo
    echo "HTTP Mode (Development):"
    echo "  • Port: 8081"
    echo "  • Security: Basic (no encryption)"
    echo "  • Certificates: None required"
    echo "  • Usage: Development and testing"
    echo
    echo "HTTPS Mode (Production):"
    echo "  • Port: 8443"
    echo "  • Security: TLS encrypted"
    echo "  • Certificates: Auto-generated self-signed"
    echo "  • Usage: Production deployment"
    echo "  • Additional headers: HSTS, security headers"
    echo
}

# Function to generate production certificates
generate_production_certs() {
    echo
    echo "🏭 Production Certificate Generation"
    echo "==================================="
    echo
    echo "For production use, you should use proper SSL certificates."
    echo "Here are some options:"
    echo
    echo "1. Let's Encrypt (free, automated):"
    echo "   certbot --nginx -d yourdomain.com"
    echo
    echo "2. Commercial SSL Certificate:"
    echo "   - Purchase from a trusted CA"
    echo "   - Generate CSR and private key"
    echo "   - Install issued certificate"
    echo
    echo "3. Self-signed (development only):"
    echo "   openssl req -x509 -newkey rsa:2048 -keyout server.key -out server.crt -days 365 -nodes"
    echo
}

# Main menu
show_menu() {
    echo
    echo "Please choose an option:"
    echo "1) Start HTTP Server (Development)"
    echo "2) Start HTTPS Server (Production)"
    echo "3) Run Tests on Both Servers"
    echo "4) Show HTTP vs HTTPS Comparison"
    echo "5) Production Certificate Guide"
    echo "6) Exit"
    echo
    read -p "Enter your choice (1-6): " choice
    
    case $choice in
        1) start_http_server ;;
        2) start_https_server ;;
        3) run_tests ;;
        4) show_comparison ;;
        5) generate_production_certs ;;
        6) echo "Goodbye! 👋"; exit 0 ;;
        *) echo "Invalid choice. Please try again."; show_menu ;;
    esac
}

# Check requirements
echo "Checking requirements..."
if ! command -v php &> /dev/null; then
    echo "❌ PHP is not installed"
    exit 1
fi

if ! command -v curl &> /dev/null; then
    echo "⚠️  curl is not installed (optional for testing)"
fi

echo "✅ Requirements satisfied"

# Show initial information
show_comparison

# Start menu loop
while true; do
    show_menu
done
