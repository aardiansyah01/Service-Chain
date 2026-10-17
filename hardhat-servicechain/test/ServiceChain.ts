import { expect } from "chai";
import { network } from "hardhat";

const { ethers } = await network.create();

describe("ServiceChain", function () {
  async function deployFixture() {
    const [admin, workshopPending, workshopActive, owner, otherUser] =
      await ethers.getSigners();

    const serviceChain = await ethers.deployContract("ServiceChain");
    await serviceChain.waitForDeployment();

    await serviceChain
      .connect(workshopPending)
      .registerWorkshop("Workshop Pending");

    await serviceChain
      .connect(workshopActive)
      .registerWorkshop("Workshop Active");

    await serviceChain.connect(admin).approveWorkshop(workshopActive.address);

    await serviceChain
      .connect(owner)
      .registerVehicle(
        "VH-001",
        "Toyota",
        "Avanza",
        "N 1234 AB",
        "Silver",
        2022,
      );

    const receiptHash = ethers.keccak256(
      ethers.toUtf8Bytes("nota-servis-test"),
    );

    return {
      serviceChain,
      admin,
      workshopPending,
      workshopActive,
      owner,
      otherUser,
      receiptHash,
    };
  }

  it("menolak bengkel berstatus PENDING mencatat servis", async function () {
    const { serviceChain, workshopPending, receiptHash } =
      await deployFixture();

    const block = await ethers.provider.getBlock("latest");
    const serviceDate = BigInt(block!.timestamp);

    await expect(
      serviceChain
        .connect(workshopPending)
        .addServiceRecord(
          "VH-001",
          serviceDate,
          10000,
          500000,
          "Oil Change",
          "Penggantian oli mesin",
          receiptHash,
        ),
    ).to.be.revertedWith("Workshop is not active");
  });

  it("mengizinkan bengkel ACTIVE mencatat servis", async function () {
    const { serviceChain, workshopActive, receiptHash } = await deployFixture();

    const block = await ethers.provider.getBlock("latest");
    const serviceDate = BigInt(block!.timestamp);

    await expect(
      serviceChain
        .connect(workshopActive)
        .addServiceRecord(
          "VH-001",
          serviceDate,
          10000,
          500000,
          "Oil Change",
          "Penggantian oli mesin",
          receiptHash,
        ),
    )
      .to.emit(serviceChain, "ServiceRecordAdded")
      .withArgs(1n, "VH-001", workshopActive.address);

    expect(await serviceChain.nextServiceId()).to.equal(2n);

    const record = await serviceChain.getServiceRecord(1n);

    expect(record.vehicleId).to.equal("VH-001");
    expect(record.workshop).to.equal(workshopActive.address);
    expect(record.serviceHash).not.to.equal(ethers.ZeroHash);
  });

  it("menolak pengguna lain mengubah kendaraan", async function () {
    const { serviceChain, otherUser } = await deployFixture();

    await expect(
      serviceChain
        .connect(otherUser)
        .updateVehicle("VH-001", "Honda", "Brio", "N 5678 CD", "Red", 2024),
    ).to.be.revertedWith("Only vehicle owner");
  });

  it("mengizinkan owner mengubah data kendaraan", async function () {
    const { serviceChain, owner } = await deployFixture();

    await expect(
      serviceChain
        .connect(owner)
        .updateVehicle("VH-001", "Honda", "Brio", "N 5678 CD", "Red", 2024),
    )
      .to.emit(serviceChain, "VehicleUpdated")
      .withArgs("VH-001");

    const vehicle = await serviceChain.getVehicle("VH-001");

    expect(vehicle.brand).to.equal("Honda");
    expect(vehicle.model).to.equal("Brio");
    expect(vehicle.licensePlate).to.equal("N 5678 CD");
    expect(vehicle.color).to.equal("Red");
    expect(vehicle.year).to.equal(2024n);
  });

  it("should allow only the vehicle owner to transfer ownership", async function () {
    const { serviceChain, owner, otherUser } = await deployFixture();

    await expect(
      serviceChain
        .connect(owner)
        .transferVehicleOwnership("VH-001", otherUser.address),
    )
      .to.emit(serviceChain, "OwnershipTransferred")
      .withArgs("VH-001", owner.address, otherUser.address);

    const vehicle = await serviceChain.getVehicle("VH-001");
    expect(vehicle.owner).to.equal(otherUser.address);

    await expect(
      serviceChain
        .connect(owner)
        .updateVehicle("VH-001", "Honda", "Brio", "N 5678 CD", "Red", 2024),
    ).to.be.revertedWith("Only vehicle owner");
  });

  it("should restrict workshop status management to admin", async function () {
    const { serviceChain, admin, workshopPending, workshopActive, otherUser } =
      await deployFixture();

    await expect(
      serviceChain.connect(otherUser).rejectWorkshop(workshopPending.address),
    ).to.be.revertedWith("Only admin");

    await serviceChain.connect(admin).rejectWorkshop(workshopPending.address);

    const rejected = await serviceChain.workshops(workshopPending.address);
    expect(rejected.status).to.equal(2n);

    await serviceChain
      .connect(admin)
      .deactivateWorkshop(workshopActive.address);

    const deactivated = await serviceChain.workshops(workshopActive.address);
    expect(deactivated.status).to.equal(3n);
  });

  it("should allow workshop or admin to cancel a service record", async function () {
    const { serviceChain, admin, workshopActive, otherUser, receiptHash } =
      await deployFixture();

    const block = await ethers.provider.getBlock("latest");
    const serviceDate = BigInt(block!.timestamp);

    // Create service record 1
    await serviceChain
      .connect(workshopActive)
      .addServiceRecord(
        "VH-001",
        serviceDate,
        10000,
        500000,
        "Oil Change",
        "Penggantian oli mesin",
        receiptHash,
      );

    // Create service record 2
    await serviceChain
      .connect(workshopActive)
      .addServiceRecord(
        "VH-001",
        serviceDate,
        15000,
        750000,
        "Brake Service",
        "Pemeriksaan rem",
        receiptHash,
      );

    // Unauthorized account cannot cancel
    await expect(
      serviceChain.connect(otherUser).cancelServiceRecord(1n),
    ).to.be.revertedWith("Not authorized");

    // Workshop can cancel its own record
    await expect(serviceChain.connect(workshopActive).cancelServiceRecord(1n))
      .to.emit(serviceChain, "ServiceRecordCancelled")
      .withArgs(1n);

    const record1 = await serviceChain.getServiceRecord(1n);
    expect(record1.cancelled).to.equal(true);

    // Admin can cancel another record
    await expect(serviceChain.connect(admin).cancelServiceRecord(2n))
      .to.emit(serviceChain, "ServiceRecordCancelled")
      .withArgs(2n);

    const record2 = await serviceChain.getServiceRecord(2n);
    expect(record2.cancelled).to.equal(true);
  });
});
