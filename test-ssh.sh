#!/bin/bash

###############################################################################
# ChronoFront - Quick SSH Test (Before Installation)
# Run this script to test SSH connectivity and verify Pi compatibility
###############################################################################

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║      ChronoFront - Pre-Installation SSH Test              ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Check if running on Pi
echo -e "${BLUE}[1/7]${NC} Detecting hardware..."
if grep -q "Raspberry Pi" /proc/cpuinfo 2>/dev/null; then
    MODEL=$(grep "Model" /proc/cpuinfo | cut -d':' -f2 | xargs)
    echo -e "${GREEN}✓${NC} Raspberry Pi detected: ${MODEL}"
else
    echo -e "${YELLOW}⚠${NC} Not a Raspberry Pi (or unknown model)"
    echo -e "    This is OK if you're testing on another system"
fi
echo ""

# Check OS version
echo -e "${BLUE}[2/7]${NC} Checking OS version..."
if [ -f /etc/os-release ]; then
    . /etc/os-release
    echo -e "${GREEN}✓${NC} OS: ${PRETTY_NAME}"

    if [[ "$VERSION_ID" == "10" ]]; then
        echo -e "${GREEN}✓${NC} Raspbian Buster (Debian 10) - Perfect!"
    elif [[ "$VERSION_ID" == "11" ]]; then
        echo -e "${YELLOW}⚠${NC} Debian 11 (Bullseye) - May need PHP adjustments"
    else
        echo -e "${YELLOW}⚠${NC} OS version: ${VERSION_ID} - Compatibility not guaranteed"
    fi
else
    echo -e "${RED}✗${NC} Cannot detect OS version"
fi
echo ""

# Check available RAM
echo -e "${BLUE}[3/7]${NC} Checking memory..."
TOTAL_RAM=$(free -m | awk '/^Mem:/{print $2}')
echo -e "${GREEN}✓${NC} Total RAM: ${TOTAL_RAM} MB"
if [ "$TOTAL_RAM" -lt 512 ]; then
    echo -e "${RED}✗${NC} Less than 512MB RAM - Installation may fail"
elif [ "$TOTAL_RAM" -lt 1024 ]; then
    echo -e "${YELLOW}⚠${NC} Less than 1GB RAM - May be slow"
else
    echo -e "${GREEN}✓${NC} RAM is sufficient"
fi
echo ""

# Check disk space
echo -e "${BLUE}[4/7]${NC} Checking disk space..."
FREE_SPACE=$(df -h / | awk 'NR==2 {print $4}')
FREE_SPACE_MB=$(df -m / | awk 'NR==2 {print $4}')
echo -e "${GREEN}✓${NC} Free space: ${FREE_SPACE}"
if [ "$FREE_SPACE_MB" -lt 500 ]; then
    echo -e "${RED}✗${NC} Less than 500MB free - Need at least 1GB for installation"
elif [ "$FREE_SPACE_MB" -lt 1024 ]; then
    echo -e "${YELLOW}⚠${NC} Less than 1GB free - Installation may work but will be tight"
else
    echo -e "${GREEN}✓${NC} Disk space is sufficient"
fi
echo ""

# Check internet connectivity
echo -e "${BLUE}[5/7]${NC} Testing internet connectivity..."
if ping -c 1 8.8.8.8 &> /dev/null; then
    echo -e "${GREEN}✓${NC} Internet connection OK"
else
    echo -e "${RED}✗${NC} No internet connection - Required for installation"
fi

if ping -c 1 github.com &> /dev/null; then
    echo -e "${GREEN}✓${NC} Can reach GitHub"
else
    echo -e "${YELLOW}⚠${NC} Cannot reach GitHub - May affect Composer"
fi
echo ""

# Check if running as root
echo -e "${BLUE}[6/7]${NC} Checking user permissions..."
if [ "$EUID" -eq 0 ]; then
    echo -e "${GREEN}✓${NC} Running as root"
else
    echo -e "${YELLOW}⚠${NC} Not running as root - Installation will need sudo"
fi
echo ""

# Check PHP if installed
echo -e "${BLUE}[7/7]${NC} Checking current PHP installation (if any)..."
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -r 'echo PHP_VERSION;')
    echo -e "${GREEN}✓${NC} PHP already installed: ${PHP_VERSION}"

    if [[ "$PHP_VERSION" =~ ^7\.3 ]]; then
        echo -e "${GREEN}✓${NC} PHP 7.3 detected - Perfect!"
    elif [[ "$PHP_VERSION" =~ ^7\. ]]; then
        echo -e "${YELLOW}⚠${NC} PHP 7.x detected - May need upgrade to 7.3"
    elif [[ "$PHP_VERSION" =~ ^8\. ]]; then
        echo -e "${YELLOW}⚠${NC} PHP 8.x detected - Need to install PHP 7.3 separately"
    fi
else
    echo -e "${BLUE}ℹ${NC} PHP not installed - Will be installed during setup"
fi
echo ""

# Summary
echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║                         Summary                            ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo "System is ready for ChronoFront installation ✓"
echo ""
echo "Next steps:"
echo "  1. Transfer files: rsync -avz . pi@IP:/home/pi/chronofront/"
echo "  2. SSH to Pi: ssh pi@IP"
echo "  3. Run: cd /home/pi/chronofront && sudo ./install-pi.sh"
echo ""
echo -e "${YELLOW}IMPORTANT:${NC} Make sure you have your reader serial number ready!"
echo ""
