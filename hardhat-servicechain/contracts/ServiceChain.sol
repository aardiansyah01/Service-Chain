 // SPDX-License-Identifier: MIT
pragma solidity ^0.8.34;

contract ServiceChain {
    address public admin;

    enum WorkshopStatus {
        PENDING,
        ACTIVE,
        REJECTED,
        INACTIVE
    }

    struct Workshop {
        string name;
        WorkshopStatus status;
        bool registered;
    }

    struct Vehicle {
        string vehicleId;
        address owner;
        string brand;
        string model;
        string licensePlate;
        bool active;
        bool registered;
    }

    struct ServiceRecord {
        uint256 serviceId;
        string vehicleId;
        address workshop;
        uint256 serviceDate;
        uint256 mileage;
        uint256 cost;
        string serviceType;
        string description;
        bool exists;
        bool cancelled;
    }

    mapping(address => Workshop) public workshops;
    mapping(string => Vehicle) private vehicles;
    mapping(uint256 => ServiceRecord) private serviceRecords;
    mapping(string => uint256[]) private vehicleServiceHistory;

    uint256 public nextServiceId = 1;

    event WorkshopRegistered(address indexed wallet, string name);
    event WorkshopStatusChanged(
        address indexed wallet,
        WorkshopStatus status
    );

    event VehicleRegistered(
        string indexed vehicleId,
        address indexed owner
    );

    event VehicleUpdated(string indexed vehicleId);
    event VehicleDeactivated(string indexed vehicleId);

    event OwnershipTransferred(
        string indexed vehicleId,
        address indexed previousOwner,
        address indexed newOwner
    );

    event ServiceRecordAdded(
        uint256 indexed serviceId,
        string indexed vehicleId,
        address indexed workshop
    );

    event ServiceRecordCancelled(uint256 indexed serviceId);

    modifier onlyAdmin() {
        require(msg.sender == admin, "Only admin");
        _;
    }

    modifier onlyWorkshop() {
        require(
            workshops[msg.sender].registered &&
            workshops[msg.sender].status == WorkshopStatus.ACTIVE,
            "Workshop is not active"
        );
        _;
    }

    modifier onlyVehicleOwner(string memory vehicleId) {
        require(
            vehicles[vehicleId].registered &&
            vehicles[vehicleId].owner == msg.sender,
            "Only vehicle owner"
        );
        _;
    }

    constructor() {
        admin = msg.sender;
    }

    // WORKSHOP

    function registerWorkshop(string calldata name) external {
        require(bytes(name).length > 0, "Name required");
        require(!workshops[msg.sender].registered, "Already registered");

        workshops[msg.sender] = Workshop({
            name: name,
            status: WorkshopStatus.PENDING,
            registered: true
        });

        emit WorkshopRegistered(msg.sender, name);
        emit WorkshopStatusChanged(
            msg.sender,
            WorkshopStatus.PENDING
        );
    }

    function approveWorkshop(address wallet) external onlyAdmin {
        require(workshops[wallet].registered, "Workshop not found");

        workshops[wallet].status = WorkshopStatus.ACTIVE;

        emit WorkshopStatusChanged(
            wallet,
            WorkshopStatus.ACTIVE
        );
    }

    function rejectWorkshop(address wallet) external onlyAdmin {
        require(workshops[wallet].registered, "Workshop not found");

        workshops[wallet].status = WorkshopStatus.REJECTED;

        emit WorkshopStatusChanged(
            wallet,
            WorkshopStatus.REJECTED
        );
    }

    function deactivateWorkshop(address wallet) external onlyAdmin {
        require(workshops[wallet].registered, "Workshop not found");

        workshops[wallet].status = WorkshopStatus.INACTIVE;

        emit WorkshopStatusChanged(
            wallet,
            WorkshopStatus.INACTIVE
        );
    }

    // VEHICLE

    function registerVehicle(
        string calldata vehicleId,
        string calldata brand,
        string calldata model,
        string calldata licensePlate
    ) external {
        require(bytes(vehicleId).length > 0, "Vehicle ID required");
        require(bytes(brand).length > 0, "Brand required");
        require(bytes(model).length > 0, "Model required");
        require(
            !vehicles[vehicleId].registered,
            "Vehicle already registered"
        );

        vehicles[vehicleId] = Vehicle({
            vehicleId: vehicleId,
            owner: msg.sender,
            brand: brand,
            model: model,
            licensePlate: licensePlate,
            active: true,
            registered: true
        });

        emit VehicleRegistered(vehicleId, msg.sender);
    }

    function updateVehicle(
        string calldata vehicleId,
        string calldata brand,
        string calldata model,
        string calldata licensePlate
    ) external onlyVehicleOwner(vehicleId) {
        require(vehicles[vehicleId].active, "Vehicle inactive");

        vehicles[vehicleId].brand = brand;
        vehicles[vehicleId].model = model;
        vehicles[vehicleId].licensePlate = licensePlate;

        emit VehicleUpdated(vehicleId);
    }

    function deactivateVehicle(
        string calldata vehicleId
    ) external onlyVehicleOwner(vehicleId) {
        require(vehicles[vehicleId].active, "Vehicle inactive");

        vehicles[vehicleId].active = false;

        emit VehicleDeactivated(vehicleId);
    }

    function transferVehicleOwnership(
        string calldata vehicleId,
        address newOwner
    ) external onlyVehicleOwner(vehicleId) {
        require(vehicles[vehicleId].active, "Vehicle inactive");
        require(newOwner != address(0), "Invalid new owner");
        require(newOwner != msg.sender, "Already the owner");

        address previousOwner = msg.sender;
        vehicles[vehicleId].owner = newOwner;

        emit OwnershipTransferred(
            vehicleId,
            previousOwner,
            newOwner
        );
    }

    function getVehicle(
        string calldata vehicleId
    ) external view returns (Vehicle memory) {
        require(vehicles[vehicleId].registered, "Vehicle not found");
        return vehicles[vehicleId];
    }

    // SERVICE RECORDS

    function addServiceRecord(
        string calldata vehicleId,
        uint256 serviceDate,
        uint256 mileage,
        uint256 cost,
        string calldata serviceType,
        string calldata description
    ) external onlyWorkshop returns (uint256) {
        require(vehicles[vehicleId].registered, "Vehicle not found");
        require(vehicles[vehicleId].active, "Vehicle inactive");
        require(serviceDate <= block.timestamp, "Invalid service date");
        require(bytes(serviceType).length > 0, "Service type required");

        uint256 serviceId = nextServiceId++;

        serviceRecords[serviceId] = ServiceRecord({
            serviceId: serviceId,
            vehicleId: vehicleId,
            workshop: msg.sender,
            serviceDate: serviceDate,
            mileage: mileage,
            cost: cost,
            serviceType: serviceType,
            description: description,
            exists: true,
            cancelled: false
        });

        vehicleServiceHistory[vehicleId].push(serviceId);

        emit ServiceRecordAdded(
            serviceId,
            vehicleId,
            msg.sender
        );

        return serviceId;
    }

    function cancelServiceRecord(
        uint256 serviceId
    ) external {
        ServiceRecord storage record = serviceRecords[serviceId];

        require(record.exists, "Record not found");
        require(!record.cancelled, "Already cancelled");
        require(
            msg.sender == record.workshop || msg.sender == admin,
            "Not authorized"
        );

        record.cancelled = true;

        emit ServiceRecordCancelled(serviceId);
    }

    function getServiceRecord(
        uint256 serviceId
    ) external view returns (ServiceRecord memory) {
        require(
            serviceRecords[serviceId].exists,
            "Record not found"
        );

        return serviceRecords[serviceId];
    }

    function getVehicleServiceHistory(
        string calldata vehicleId
    ) external view returns (uint256[] memory) {
        require(vehicles[vehicleId].registered, "Vehicle not found");
        return vehicleServiceHistory[vehicleId];
    }
}
