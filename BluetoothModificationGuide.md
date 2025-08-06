# Bluetooth Duplicate Connection Fix - Where to Change Code

## Problem Summary
- Connected headset appears in discovery list
- Tapping connected device creates duplicate connection
- Automobile screen shows 2 same devices

## Key Locations to Modify

### 1. **Discovery List Filtering** (Most Important)
**Location**: Where you handle `BluetoothDevice.ACTION_FOUND` broadcast

**Current Code Pattern**:
```java
// In your BroadcastReceiver
case BluetoothDevice.ACTION_FOUND:
    BluetoothDevice device = intent.getParcelableExtra(BluetoothDevice.EXTRA_DEVICE);
    discoveredDevices.add(device);  // ← CHANGE HERE
    updateDiscoveryUI();
    break;
```

**Change To**:
```java
case BluetoothDevice.ACTION_FOUND:
    BluetoothDevice device = intent.getParcelableExtra(BluetoothDevice.EXTRA_DEVICE);
    
    // ← ADD THIS CHECK
    if (!isDeviceAlreadyConnected(device.getAddress())) {
        discoveredDevices.add(device);
        updateDiscoveryUI();
    }
    break;
```

### 2. **Connection Validation**
**Location**: Where you handle device connection (usually in onClick or connection method)

**Current Code Pattern**:
```java
public void connectToDevice(BluetoothDevice device) {
    // Direct connection attempt
    device.createBond();  // ← CHANGE HERE
    // or your specific connection method
}
```

**Change To**:
```java
public void connectToDevice(BluetoothDevice device) {
    String macAddress = device.getAddress();
    
    // ← ADD THIS CHECK
    if (connectedDeviceMacs.contains(macAddress)) {
        Log.w(TAG, "Device already connected: " + macAddress);
        // Optionally disconnect existing connection first
        disconnectDevice(macAddress);
    }
    
    // Add to connected list before connecting
    connectedDeviceMacs.add(macAddress);
    device.createBond();
}
```

### 3. **MAC Address Tracking**
**Location**: Add this as a class member where you manage Bluetooth connections

**Add This**:
```java
// Add as class member
private Set<String> connectedDeviceMacs = new HashSet<>();

// Add this helper method
private boolean isDeviceAlreadyConnected(String macAddress) {
    return connectedDeviceMacs.contains(macAddress);
}
```

### 4. **Connection State Monitoring**
**Location**: In your existing BroadcastReceiver (same place as ACTION_FOUND)

**Add These Cases**:
```java
case BluetoothDevice.ACTION_ACL_CONNECTED:
    BluetoothDevice connectedDevice = intent.getParcelableExtra(BluetoothDevice.EXTRA_DEVICE);
    connectedDeviceMacs.add(connectedDevice.getAddress());  // ← ADD THIS
    break;

case BluetoothDevice.ACTION_ACL_DISCONNECTED:
    BluetoothDevice disconnectedDevice = intent.getParcelableExtra(BluetoothDevice.EXTRA_DEVICE);
    connectedDeviceMacs.remove(disconnectedDevice.getAddress());  // ← ADD THIS
    break;
```

## Specific Files to Look For

### Common File Names in Automotive Bluetooth:
- `BluetoothService.java` or `BluetoothManager.java`
- `BluetoothDeviceListAdapter.java`
- `BluetoothActivity.java` or `PairingActivity.java`
- `CarBluetoothService.java`
- Files in `/src/com/android/bluetooth/` directory

### Look for These Patterns:
1. **ACTION_FOUND handler**: Search for `ACTION_FOUND`
2. **Device list adapter**: Search for `BluetoothDevice` and `ArrayList`
3. **Connection method**: Search for `createBond()` or `connect()`
4. **BroadcastReceiver**: Search for `BroadcastReceiver` and `onReceive`

## Quick Search Commands
Use these to find the right files:

```bash
# Find files with Bluetooth discovery
grep -r "ACTION_FOUND" /path/to/your/project

# Find connection handling
grep -r "createBond\|connect" /path/to/your/project

# Find device list management  
grep -r "BluetoothDevice.*add\|ArrayList.*BluetoothDevice" /path/to/your/project
```

## Minimal Changes Summary

**Only 3 changes needed:**

1. **Filter discovery list**: Add MAC address check in `ACTION_FOUND`
2. **Track connections**: Add MAC addresses to a Set when connected/disconnected  
3. **Validate connections**: Check if device already connected before connecting

This will solve your duplicate device issue with minimal code changes.